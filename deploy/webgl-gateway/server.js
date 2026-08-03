'use strict';

const fs = require('fs');
const http = require('http');
const net = require('net');
const path = require('path');
const { WebSocket, WebSocketServer } = require('ws');

const listenHost = process.env.LISTEN_HOST || '127.0.0.1';
const listenPort = parsePort(process.env.LISTEN_PORT || '8080', 'LISTEN_PORT');
const targetHost = process.env.TARGET_HOST || '127.0.0.1';
const allowedTargetPorts = new Set(
  (process.env.TARGET_PORTS || '14445')
    .split(',')
    .map((value) => parsePort(value.trim(), 'TARGET_PORTS'))
);
const buildDirectory = path.resolve(
  process.env.WEBGL_BUILD_DIR || path.join(__dirname, '..', '..', 'public', 'play')
);

const securityHeaders = {
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY',
  'Referrer-Policy': 'no-referrer',
  'Permissions-Policy': 'camera=(), microphone=(), geolocation=(), payment=()',
  'Cross-Origin-Resource-Policy': 'same-origin',
  'Content-Security-Policy': "default-src 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'none'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; media-src 'self' blob:; font-src 'self' data:; worker-src 'self' blob:; child-src 'self' blob:; connect-src 'self' ws: wss:; manifest-src 'self'"
};

const mimeTypes = {
  '.css': 'text/css; charset=utf-8',
  '.data': 'application/octet-stream',
  '.html': 'text/html; charset=utf-8',
  '.ico': 'image/x-icon',
  '.jpeg': 'image/jpeg',
  '.jpg': 'image/jpeg',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.mem': 'application/octet-stream',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.wasm': 'application/wasm',
  '.webmanifest': 'application/manifest+json; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8'
};

const server = http.createServer((request, response) => {
  const requestUrl = new URL(request.url, 'http://localhost');
  if (requestUrl.pathname === '/health') {
    sendJson(response, 200, {
      ok: true,
      buildExists: fs.existsSync(buildDirectory)
    });
    return;
  }

  serveBuildFile(requestUrl.pathname, response);
});

const webSocketServer = new WebSocketServer({
  noServer: true,
  maxPayload: 32 * 1024 * 1024,
  perMessageDeflate: false
});

server.on('upgrade', (request, socket, head) => {
  let requestUrl;
  try {
    requestUrl = new URL(request.url, 'http://localhost');
  } catch (error) {
    rejectUpgrade(socket, 400, 'Bad Request');
    return;
  }

  if (requestUrl.pathname !== '/game') {
    rejectUpgrade(socket, 404, 'Not Found');
    return;
  }

  let targetPort;
  try {
    targetPort = parsePort(requestUrl.searchParams.get('port') || '', 'port');
  } catch (error) {
    rejectUpgrade(socket, 400, 'Invalid target port');
    return;
  }

  if (!allowedTargetPorts.has(targetPort)) {
    rejectUpgrade(socket, 403, 'Target port is not allowed');
    return;
  }

  request.nroTargetPort = targetPort;
  webSocketServer.handleUpgrade(request, socket, head, (webSocket) => {
    webSocketServer.emit('connection', webSocket, request);
  });
});

webSocketServer.on('connection', (webSocket, request) => {
  const targetPort = request.nroTargetPort;
  const clientAddress = request.socket.remoteAddress || 'unknown';
  const tcpSocket = net.createConnection({
    host: targetHost,
    port: targetPort
  });
  let closed = false;

  console.log(`[gateway] ${clientAddress} connected`);

  webSocket.on('message', (payload, isBinary) => {
    if (!isBinary) {
      webSocket.close(1003, 'Binary messages only');
      return;
    }

    if (!tcpSocket.destroyed) {
      tcpSocket.write(payload);
    }
  });

  webSocket.on('close', () => {
    closeBoth('websocket closed');
  });

  webSocket.on('error', (error) => {
    console.error(`[gateway] WebSocket error for ${clientAddress}:`, error.message);
    closeBoth('websocket error');
  });

  tcpSocket.on('data', (payload) => {
    if (webSocket.readyState === WebSocket.OPEN) {
      webSocket.send(payload, { binary: true });
    }
  });

  tcpSocket.on('close', () => {
    closeBoth('tcp closed');
  });

  tcpSocket.on('error', (error) => {
    console.error(`[gateway] TCP error for ${clientAddress}:`, error.message);
    closeBoth('tcp error');
  });

  function closeBoth(reason) {
    if (closed) {
      return;
    }
    closed = true;
    console.log(`[gateway] ${clientAddress} disconnected (${reason})`);

    if (!tcpSocket.destroyed) {
      tcpSocket.destroy();
    }
    if (webSocket.readyState === WebSocket.OPEN ||
        webSocket.readyState === WebSocket.CONNECTING) {
      webSocket.close(1011, reason);
    }
  }
});

server.listen(listenPort, listenHost, () => {
  console.log(`[web] http://localhost:${listenPort}`);
  console.log('[gateway] ready');
});

function serveBuildFile(urlPath, response) {
  let decodedPath;
  try {
    decodedPath = decodeURIComponent(urlPath);
  } catch (error) {
    sendText(response, 400, 'Invalid URL encoding.');
    return;
  }

  if (decodedPath === '/') {
    decodedPath = '/index.html';
  }

  const relativePath = decodedPath.replace(/^[/\\]+/, '');
  const filePath = path.resolve(buildDirectory, relativePath);
  const relativeToBuild = path.relative(buildDirectory, filePath);
  if (relativeToBuild.startsWith('..') || path.isAbsolute(relativeToBuild)) {
    sendText(response, 403, 'Forbidden.');
    return;
  }

  fs.stat(filePath, (statError, stats) => {
    if (statError || !stats.isFile()) {
      if (!fs.existsSync(buildDirectory)) {
        sendText(
          response,
          503,
          `Unity WebGL build was not found.\nBuild the project into:\n${buildDirectory}\n`
        );
        return;
      }
      sendText(response, 404, 'File not found.');
      return;
    }

    const headers = buildHeaders(filePath);
    response.writeHead(200, headers);
    const stream = fs.createReadStream(filePath);
    stream.on('error', (error) => {
      console.error('[web] Read error:', error.message);
      response.destroy(error);
    });
    stream.pipe(response);
  });
}

function buildHeaders(filePath) {
  let contentPath = filePath;
  let contentEncoding;
  if (filePath.endsWith('.br')) {
    contentEncoding = 'br';
    contentPath = filePath.slice(0, -3);
  } else if (filePath.endsWith('.gz')) {
    contentEncoding = 'gzip';
    contentPath = filePath.slice(0, -3);
  }

  const extension = path.extname(contentPath).toLowerCase();
  const headers = {
    'Content-Type': mimeTypes[extension] || 'application/octet-stream',
    ...securityHeaders
  };

  if (contentEncoding) {
    headers['Content-Encoding'] = contentEncoding;
  }

  headers['Cache-Control'] = 'no-store';

  return headers;
}

function rejectUpgrade(socket, statusCode, message) {
  socket.end(
    `HTTP/1.1 ${statusCode} ${message}\r\n` +
    'Connection: close\r\n' +
    'Content-Type: text/plain; charset=utf-8\r\n' +
    `Content-Length: ${Buffer.byteLength(message)}\r\n\r\n` +
    message
  );
}

function parsePort(value, settingName) {
  if (!/^\d+$/.test(String(value))) {
    throw new Error(`${settingName} must be a TCP port.`);
  }
  const port = Number(value);
  if (!Number.isInteger(port) || port < 1 || port > 65535) {
    throw new Error(`${settingName} must be between 1 and 65535.`);
  }
  return port;
}

function sendJson(response, statusCode, value) {
  const body = JSON.stringify(value, null, 2);
  response.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    'Cache-Control': 'no-store'
  });
  response.end(body);
}

function sendText(response, statusCode, body) {
  response.writeHead(statusCode, {
    'Content-Type': 'text/plain; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    'Cache-Control': 'no-store'
  });
  response.end(body);
}
