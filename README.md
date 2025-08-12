# PalStory Application

A web-based storytelling game built with PHP, JavaScript, and MySQL.

## Overview

PalStory is an interactive web game where players can create characters, explore locations, encounter monsters, and engage in battles. The application features a real-time game interface with image assets, sound effects, and a dynamic storyline system.

## Features

- **Character Creation**: Choose from multiple character portraits and create custom players
- **Interactive Gameplay**: Move through locations using WASD keys or mouse clicks
- **Monster Encounters**: Battle system with attack/defense mechanics
- **Item Management**: Collect, equip, and manage items with stats
- **Sound System**: Background music and sound effects
- **Responsive UI**: Modern web interface with NES-style CSS framework

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Web server (Apache recommended)
- Modern web browser with JavaScript support

## Installation

### Quick Start with Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd palstory-ai/palstory-lamp
   ```

2. **Start the application**
   ```bash
   docker-compose up -d
   ```

3. **Visit the game**
   - Open http://localhost/story in your browser
   - **That's it!** Database and configuration are handled automatically.

### Manual Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd palstory-ai/palstory
   ```

2. **Set up the database**
   - Import the `story.sql` file into your MySQL database
   - Update database credentials in `src/html/story/config.php`

3. **Configure image assets**
   - Copy `src/html/config.php.example` to `src/html/story/config.php`
   - Copy `src/html/config.js.example` to `src/html/story/config.js`
   - Update the `$IMAGE_BASE_URL` and `IMAGE_BASE_URL` settings to point to your image assets

4. **Set up web server**
   - Point your web server document root to `src/html/`
   - Ensure PHP is properly configured
   - Make sure the `uploads/` directory is accessible

## Configuration

### Database Configuration (`src/html/story/config.php`)
```php
$DB_SERVER = "localhost";
$DB_USERNAME = "your_username";
$DB_PASSWORD = "your_password";
$DB_NAME = "story";
$IMAGE_BASE_URL = "https://your-domain.com/story/uploads/";
```

### JavaScript Configuration (`src/html/story/config.js`)
```javascript
const PALSTORY_CONFIG = {
    IMAGE_BASE_URL: "https://your-domain.com/story/uploads/"
};
```

## Game Assets

The game requires image assets to be available at the configured `IMAGE_BASE_URL`. Required asset categories include:

- **Character Portraits**: `p_*.png` files for player avatars
- **Monster Images**: Various monster sprites
- **Item Images**: Equipment and item icons
- **Location Backgrounds**: Environment images
- **Sound Files**: `.mp3` files for audio effects

## Usage

1. **Access the game**: Navigate to `/story/` in your web browser
2. **Create a game**: Enter a game name and player name
3. **Select character**: Choose your character portrait
4. **Play**: Use WASD keys or mouse to move and interact

### Keyboard Controls
- **WASD/Arrow Keys**: Movement
- **H**: Toggle help dialog
- **I**: Toggle items table
- **C**: Toggle character stats
- **M**: Attack monster (when in combat)
- **Z/X**: Location info/stats
- **V/B/N**: Monster info/stats/battle log

## Development

### File Structure
```
src/html/
├── config.php.example      # Example PHP config (copy to story/config.php)
├── config.js.example       # Example JS config (copy to story/config.js)
├── story/
│   ├── assets/             # Platform icons, manifest, favicon
│   ├── css/
│   │   └── styles.css      # Stylesheet
│   ├── js/                 # App JavaScript modules
│   │   ├── vendor/         # Third-party libs (e.g., jQuery)
│   │   └── *.js            # Modularized game scripts
│   ├── config.php          # Database and image configuration
│   ├── config.js           # JavaScript configuration
│   ├── index.php           # Game lobby/start page
│   ├── game.php            # Main game interface
│   ├── create.php          # Admin interface for content creation
│   └── createServer.php    # Server-side content creation logic
```

### Key Components
- **Game Engine**: JavaScript-based with canvas rendering
- **Database Layer**: PHP with MySQLi prepared statements
- **Asset Management**: Centralized image URL configuration
- **UI Framework**: NES.css for retro styling

## Docker Support

The recommended way to run PalStory is using the companion `palstory-lamp` Docker setup:

```bash
cd palstory-ai/palstory-lamp
docker-compose up -d
```

This provides a complete LAMP stack with automatic database initialization - no manual setup required!

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

## Author

**Johan Litsfeldt (johlits)**  
Copyright 2025

## Support

For issues and questions, please create an issue in the repository or contact the development team.
