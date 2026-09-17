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

export type EquipmentSlotOption = {
    value: string;
    label: string;
    column: 'left' | 'right' | 'bottom';
};

export type ItemSummary = {
    id: number;
    name: string;
    icon: string | null;
    quality: string;
    quality_label: string;
    quality_color: string;
    item_level: number;
};

export type CharacterEquipment = {
    slot: string;
    item: ItemSummary;
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
    is_public: boolean;
    public_url: string | null;
    average_item_level: number | null;
    professions: CharacterProfession[];
    equipment: CharacterEquipment[];
};

export type CharacterFormOptions = {
    factions: FactionOption[];
    classes: CharacterClassOption[];
    professions: ProfessionOption[];
    races: RaceOption[];
    equipmentSlots: EquipmentSlotOption[];
};
