export type Faction = 'alliance' | 'horde';

export type FactionOption = {
    value: Faction;
    label: string;
};

export type CharacterClassOption = {
    value: string;
    label: string;
    color: string;
    needsTextOutline: boolean;
};

export type ProfessionOption = {
    value: string;
    label: string;
    isPrimary: boolean;
};

export type RaceOption = {
    value: string;
    label: string;
    faction: Faction;
};

export type CharacterProfession = {
    id: number;
    name: string;
    label: string;
    skill_level: number | null;
};

export type Character = {
    id: number;
    name: string;
    faction: Faction;
    class: string;
    class_label: string;
    class_color: string;
    class_needs_text_outline: boolean;
    spec: string | null;
    race: string | null;
    race_label: string | null;
    level: number | null;
    position: number;
    professions: CharacterProfession[];
};

export type CharacterFormOptions = {
    factions: FactionOption[];
    classes: CharacterClassOption[];
    professions: ProfessionOption[];
    races: RaceOption[];
};
