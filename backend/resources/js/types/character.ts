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
    iconUrl: string;
};

export type ProfessionOption = {
    value: string;
    label: string;
    isPrimary: boolean;
    iconUrl: string;
};

export type RaceOption = {
    value: string;
    label: string;
    faction: Faction;
    iconUrl: string;
};

export type CharacterProfession = {
    id: number;
    name: string;
    label: string;
    icon_url: string;
    skill_level: number | null;
};

export type EquipmentSlotOption = {
    value: string;
    label: string;
    column: 'left' | 'right' | 'bottom';
};

export type ItemSummary = {
    id: number;
    name: string;
    icon: string | null;
    icon_url: string | null;
    quality: string;
    quality_label: string;
    quality_color: string;
    item_level: number;
    gem_color: number | null;
    socket_colors: number[];
};

export type SocketedGem = {
    socket_position: number;
    item: {
        id: number;
        name: string;
        icon_url: string | null;
        quality_color: string;
        gem_color: number | null;
    };
};

export type CharacterEquipment = {
    slot: string;
    item: ItemSummary;
    gems: SocketedGem[];
};

export type SpecOption = {
    value: string;
    label: string;
    class: string;
    iconUrl: string;
};

export type CharacterSpecData = {
    id: number;
    value: string;
    label: string;
    icon_url: string;
    position: 1 | 2;
    average_item_level: number | null;
    equipment: CharacterEquipment[];
};

export type Character = {
    id: number;
    name: string;
    faction: Faction;
    class: string;
    class_label: string;
    class_color: string;
    class_needs_text_outline: boolean;
    class_icon_url: string;
    race: string | null;
    race_label: string | null;
    race_icon_url: string | null;
    level: number | null;
    position: number;
    is_public: boolean;
    public_url: string | null;
    average_item_level: number | null;
    professions: CharacterProfession[];
    specs: CharacterSpecData[];
};

export type CharacterFormOptions = {
    factions: FactionOption[];
    classes: CharacterClassOption[];
    specs: SpecOption[];
    professions: ProfessionOption[];
    races: RaceOption[];
    equipmentSlots: EquipmentSlotOption[];
};
