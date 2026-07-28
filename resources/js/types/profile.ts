export type CharacterLayerKey = "leg" | "body" | "head" | string;

export interface CharacterLayer {
    key: CharacterLayerKey;
    part_id: number;
    icon_id: number;
    url: string;
    x: number;
    y: number;
    z_index: number;
}

export interface CharacterAppearance {
    mode: "costume" | "default";
    costume_id: number | null;
    costume_name: string | null;
    parts: {
        head: number;
        body: number;
        leg: number;
    };
    pose: {
        key: "idle-right" | string;
        zoom: number;
        origin: "bottom-center" | string;
    };
    layers: CharacterLayer[];
    extensions: CharacterLayer[];
    complete: boolean;
}

export interface ProfileUser {
    username: string;
    email?: string | null;
    cash: number;
    danap: number;
    active: number;
}

export interface PlayerStats {
    potential: number;
    hp: number;
    ki: number;
    damage: number;
    defense: number;
    critical: number;
}

export interface PlayerInventory {
    gold: number;
    gem: number;
    ruby: number;
    thoi_vang: number;
}

export interface ProfilePlayer {
    has_character: boolean;
    name?: string;
    power?: number;
    task_name?: string;
    gender_text?: string;
    avatar_url?: string;
    appearance?: CharacterAppearance;
    stats?: PlayerStats;
    inventory?: PlayerInventory;
}

export interface ProfileData {
    user: ProfileUser;
    player: ProfilePlayer;
}
