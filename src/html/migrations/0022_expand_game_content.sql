-- 0022_expand_game_content.sql
-- NOTE: The monsters and locations from this migration were already added to the database
-- (likely via direct SQL or an earlier manual import). They are present as:
-- - Monsters: IDs 119-186 (Forest Troll, Dire Bear, etc.)
-- - Locations: IDs 136-160 (Whispering Woods, Frostpeak Mountains, etc.)
-- This migration now only adds the schema change and items to complete the content expansion.

START TRANSACTION;

-- ============================================================================
-- SCHEMA CHANGES
-- ============================================================================

-- Add 'dungeon' to location_type ENUM (if not already added)
ALTER TABLE resources_locations 
MODIFY COLUMN location_type ENUM('wilderness', 'town', 'rest_spot', 'dungeon') NOT NULL DEFAULT 'wilderness' COMMENT 'Location type: wilderness (combat/gather), town (safe zone with services), rest_spot (HP/MP restoration), dungeon (special instances)';

-- ============================================================================
-- DATA INSERTS - ITEMS ONLY
-- ============================================================================
-- Monsters and locations are already in the database, so we only insert the items here

INSERT INTO resources_items (name, image, description, stats, banned, rarity) VALUES
-- Weapons (Level 15-20)
('Iron Longsword', 'ironlongsword.jpg', 'A well-balanced blade forged from quality iron.', 'type=weapon;atk=18-22;def=0;spd=2;crit=5;', 0, 0),
('Hunters Bow', 'huntersbow.jpg', 'A sturdy bow favored by forest hunters.', 'type=weapon;atk=16-20;def=0;spd=4;crit=12;evd=3;', 0, 0),
('Battle Axe', 'battleaxe.jpg', 'A heavy axe that cleaves through armor.', 'type=weapon;atk=22-26;def=0;spd=-2;crit=8;', 0, 1),
('Enchanted Staff', 'enchantedstaff.jpg', 'A wooden staff imbued with magical energy.', 'type=weapon;atk=14-18;def=2;spd=3;crit=6;maxmp=15;', 0, 1),

-- Weapons (Level 25-30)
('Steel Greatsword', 'steelgreatsword.jpg', 'A massive two-handed sword of exceptional quality.', 'type=weapon;atk=28-34;def=0;spd=-1;crit=10;', 0, 1),
('Flamebrand', 'flamebrand.jpg', 'A sword wreathed in eternal flames.', 'type=weapon;atk=30-36;def=0;spd=3;crit=15;', 0, 2),
('Frost Spear', 'frostspear.jpg', 'A spear tipped with ice that never melts.', 'type=weapon;atk=26-32;def=2;spd=4;crit=12;', 0, 2),
('Shadow Dagger', 'shadowdagger.jpg', 'A dagger that seems to absorb light itself.', 'type=weapon;atk=20-28;def=0;spd=8;crit=25;evd=5;', 0, 2),

-- Armor (Level 15-20)
('Iron Chainmail', 'ironchainmail.jpg', 'Interlocking iron rings provide solid protection.', 'type=armor;atk=0;def=12-16;spd=-1;maxhp=20;', 0, 0),
-- Note: 'Leather Armor' already exists in database (ID 99), skipping to avoid duplicate
('Ranger Cloak', 'rangercloak.jpg', 'A hooded cloak that helps you blend into the forest.', 'type=armor;atk=0;def=6-10;spd=4;evd=8;', 0, 1),

-- Armor (Level 25-30)
('Steel Plate Armor', 'steelplate.jpg', 'Heavy plate armor that can withstand tremendous blows.', 'type=armor;atk=0;def=20-26;spd=-3;maxhp=40;', 0, 1),
('Dragon Scale Mail', 'dragonscale.jpg', 'Armor crafted from the scales of a dragon.', 'type=armor;atk=2;def=22-28;spd=0;maxhp=35;crit=5;', 0, 2),
('Mage Robes', 'magerobes.jpg', 'Enchanted robes that enhance magical abilities.', 'type=armor;atk=0;def=10-14;spd=3;maxmp=30;evd=6;', 0, 2),
('Shadow Cloak', 'shadowcloak.jpg', 'A cloak woven from shadows that makes you harder to hit.', 'type=armor;atk=0;def=12-16;spd=5;evd=15;', 0, 2),

-- Shields (Level 15-30)
('Iron Kite Shield', 'ironkite.jpg', 'A large shield that provides excellent defense.', 'type=shield;atk=0;def=14-18;spd=-2;', 0, 0),
('Tower Shield', 'towershield.jpg', 'A massive shield that covers most of your body.', 'type=shield;atk=0;def=18-24;spd=-4;maxhp=25;', 0, 1),
('Buckler of Deflection', 'deflectionbuckler.jpg', 'A small shield enchanted to deflect attacks.', 'type=shield;atk=0;def=10-14;spd=2;evd=10;', 0, 2),

-- Accessories (Level 15-30)
('Ring of Strength', 'strengthring.jpg', 'A ring that enhances physical power.', 'type=accessory;atk=5-8;def=0;spd=0;', 0, 1),
('Amulet of Protection', 'protectionamulet.jpg', 'An amulet that wards off harm.', 'type=accessory;atk=0;def=6-10;spd=0;maxhp=15;', 0, 1),
('Boots of Speed', 'speedboots.jpg', 'Enchanted boots that increase your movement speed.', 'type=accessory;atk=0;def=0;spd=6-8;evd=5;', 0, 1),
('Cloak of Evasion', 'evasioncloak.jpg', 'A magical cloak that helps you dodge attacks.', 'type=accessory;atk=0;def=2-4;spd=3;evd=12-16;', 0, 2),
('Berserker Ring', 'berserkerring.jpg', 'A ring that greatly increases critical strike chance.', 'type=accessory;atk=3-6;def=0;spd=2;crit=15-20;', 0, 2),
('Archmage Pendant', 'archmagependant.jpg', 'A pendant that amplifies magical energy.', 'type=accessory;atk=4-7;def=0;spd=0;maxmp=25;crit=8;', 0, 2),
('Vampiric Amulet', 'vampiricamulet.jpg', 'An amulet that drains life from enemies.', 'type=accessory;atk=6-9;def=0;spd=0;maxhp=20;crit=10;', 0, 3),
('Phoenix Feather', 'phoenixfeather.jpg', 'A legendary feather that grants incredible resilience.', 'type=accessory;atk=0;def=8-12;spd=4;maxhp=30;evd=8;', 0, 3),

-- Epic Weapons (High-tier)
('Demon Slayer', 'demonslayer.jpg', 'A blade specifically crafted to destroy demons.', 'type=weapon;atk=38-46;def=2;spd=6;crit=22;', 0, 3),
('Holy Avenger', 'holyavenger.jpg', 'A sword blessed by priests. Effective against undead.', 'type=weapon;atk=36-44;def=4;spd=5;crit=18;maxhp=30;', 0, 3),
('Stormcaller', 'stormcaller.jpg', 'A staff that commands the power of storms.', 'type=weapon;atk=32-40;def=3;spd=7;crit=20;maxmp=40;', 0, 3),
('Shadowfang', 'shadowfang.jpg', 'Twin daggers that strike from the shadows.', 'type=weapon;atk=30-42;def=0;spd=12;crit=35;evd=10;', 0, 3),

-- Epic Armor (High-tier)
('Titanium Plate', 'titaniumplate.jpg', 'Armor made from the rarest metal. Incredibly durable.', 'type=armor;atk=3;def=28-36;spd=-1;maxhp=50;', 0, 3),
('Assassin Leathers', 'assassinleathers.jpg', 'Lightweight armor favored by master assassins.', 'type=armor;atk=4;def=18-24;spd=8;evd=18;crit=12;', 0, 3),
('Battlemage Vestments', 'battlemagevestments.jpg', 'Robes reinforced for combat. Perfect for warrior-mages.', 'type=armor;atk=5;def=20-26;spd=4;maxmp=35;maxhp=35;', 0, 3),

-- Epic Accessories
('Crown of Wisdom', 'crownofwisdom.jpg', 'A crown that enhances all mental abilities.', 'type=accessory;atk=8-12;def=6-10;spd=3;maxmp=40;crit=12;', 0, 3),
('Gauntlets of Power', 'gauntletsofpower.jpg', 'Gauntlets that multiply your strength tenfold.', 'type=accessory;atk=12-18;def=4-8;spd=0;crit=15;maxhp=25;', 0, 3),
('Wings of Freedom', 'wingsoffreedom.jpg', 'Ethereal wings that grant incredible mobility.', 'type=accessory;atk=0;def=0;spd=10-15;evd=20-25;', 0, 3),
('Heart of the Phoenix', 'heartofphoenix.jpg', 'A gem containing the essence of a phoenix. Grants rebirth.', 'type=accessory;atk=5-9;def=8-12;spd=5;maxhp=50;evd=10;', 0, 3),

-- Legendary Weapons
('Excalibur', 'excalibur.jpg', 'The legendary sword of kings. Glows with holy light.', 'type=weapon;atk=45-55;def=5;spd=5;crit=20;maxhp=50;', 0, 4),
('Mjolnir', 'mjolnir.jpg', 'The hammer of thunder. Strikes with the force of lightning.', 'type=weapon;atk=50-60;def=8;spd=3;crit=25;', 0, 4),
('Gungnir', 'gungnir.jpg', 'The spear that never misses its mark.', 'type=weapon;atk=42-52;def=3;spd=8;crit=30;evd=10;', 0, 4),
('Frostmourne', 'frostmourne.jpg', 'A cursed blade that steals souls. Grants immense power at a terrible price.', 'type=weapon;atk=48-58;def=0;spd=6;crit=28;maxhp=40;', 0, 4),
('Ragnarok', 'ragnarok.jpg', 'The sword of the end times. Said to be able to slay gods.', 'type=weapon;atk=55-65;def=5;spd=7;crit=35;maxhp=60;', 0, 4),

-- Legendary Armor
('Aegis Armor', 'aegisarmor.jpg', 'Divine armor blessed by the gods. Nearly impenetrable.', 'type=armor;atk=5;def=35-45;spd=0;maxhp=80;evd=5;', 0, 4),
('Dragonheart Plate', 'dragonheartplate.jpg', 'Forged from the heart of an ancient dragon. Pulses with draconic power.', 'type=armor;atk=8;def=32-42;spd=2;maxhp=70;crit=10;', 0, 4),
('Void Shroud', 'voidshroud.jpg', 'Armor woven from the fabric of the void. Makes you nearly impossible to hit.', 'type=armor;atk=3;def=25-35;spd=8;evd=25;maxhp=50;', 0, 4),
('Celestial Raiment', 'celestialraiment.jpg', 'Robes worn by celestial beings. Radiates divine energy.', 'type=armor;atk=6;def=28-38;spd=5;maxmp=60;evd=12;maxhp=60;', 0, 4),

-- Legendary Shields
('Bulwark of Ages', 'bulwarkofages.jpg', 'A shield that has protected heroes for millennia.', 'type=shield;atk=0;def=30-40;spd=-1;maxhp=60;evd=8;', 0, 4),
('Mirror Shield', 'mirrorshield.jpg', 'A shield that reflects attacks back at enemies.', 'type=shield;atk=10;def=25-35;spd=3;evd=15;crit=15;', 0, 4),

-- Legendary Accessories
('Infinity Gauntlet', 'infinitygauntlet.jpg', 'A gauntlet of ultimate power. Enhances all attributes.', 'type=accessory;atk=15-20;def=12-18;spd=8;maxhp=60;maxmp=50;crit=25;evd=15;', 0, 4),
('Eye of Eternity', 'eyeofeternity.jpg', 'An ancient artifact that sees through all illusions.', 'type=accessory;atk=10-15;def=10-15;spd=10;evd=30;crit=30;', 0, 4),
('Soul of the Dragon', 'soulofdragon.jpg', 'The crystallized soul of an ancient dragon. Grants draconic might.', 'type=accessory;atk=18-25;def=15-20;spd=6;maxhp=80;crit=28;', 0, 4),
('Orb of Omnipotence', 'orbofomnipotence.jpg', 'A sphere of pure magical energy. The ultimate arcane artifact.', 'type=accessory;atk=12-18;def=8-14;spd=7;maxmp=100;crit=22;evd=12;', 0, 4);

COMMIT;
