/**
 * PalStory Game Namespace
 * Consolidates global variables into organized namespaces to reduce pollution
 * and improve code organization.
 * 
 * This file should be loaded early, before other game scripts.
 */
(function() {
  'use strict';

  // ============================================================================
  // MAIN GAME NAMESPACE
  // ============================================================================
  
  /**
   * @namespace PalStory
   * @description Main game namespace containing all sub-modules
   */
  window.PalStory = window.PalStory || {};

  // ============================================================================
  // GAME STATE
  // ============================================================================
  
  /**
   * @namespace PalStory.State
   * @description Centralized game state management
   */
  PalStory.State = {
    // Player state
    player: {
      x: 0,
      y: 0,
      name: '',
      stats: '',
      id: null
    },
    
    // Room state
    room: {
      id: null,
      name: ''
    },
    
    // Game flags
    flags: {
      gameStarted: false,
      canMove: true,
      locationsLoaded: false,
      isInCombat: false
    },
    
    // Canvas/rendering state
    canvas: {
      width: 0,
      height: 0,
      tileSize: 64
    }
  };

  // ============================================================================
  // CONFIGURATION
  // ============================================================================
  
  /**
   * @namespace PalStory.Config
   * @description Game configuration constants
   */
  PalStory.Config = {
    // API settings
    API_BASE_URL: 'gameServer.php',
    REQUEST_TIMEOUT: 15000,
    MAX_RETRIES: 2,
    
    // Rendering settings
    DEFAULT_TILE_SIZE: 64,
    ANIMATION_SPEED: 8,
    
    // Combat settings
    COMBAT_COOLDOWN_MS: 500,
    
    // Movement settings
    MOVE_RATE_LIMIT_MS: 100
  };

  // ============================================================================
  // CONSTANTS
  // ============================================================================
  
  /**
   * @namespace PalStory.Constants
   * @description Game constants and enums
   */
  PalStory.Constants = {
    // Direction vectors
    DIRECTIONS: {
      UP: { dx: 0, dy: -1 },
      DOWN: { dx: 0, dy: 1 },
      LEFT: { dx: -1, dy: 0 },
      RIGHT: { dx: 1, dy: 0 }
    },
    
    // Component meta types
    META_TYPES: {
      PLAYER: 1,
      MONSTER: 2,
      LOCATION: 3,
      ITEM: 4
    },
    
    // Location types
    LOCATION_TYPES: {
      NORMAL: 'normal',
      TOWN: 'town',
      REST_SPOT: 'rest_spot',
      DUNGEON: 'dungeon',
      SHOP: 'shop'
    },
    
    // Skill types
    SKILL_TYPES: {
      ACTIVE: 'active',
      PASSIVE: 'passive'
    }
  };

  // ============================================================================
  // EVENTS
  // ============================================================================
  
  /**
   * @namespace PalStory.Events
   * @description Custom event dispatching and handling
   */
  PalStory.Events = {
    /**
     * Dispatch a custom game event
     * @param {string} eventName - Name of the event
     * @param {Object} [detail] - Event detail data
     */
    dispatch: function(eventName, detail) {
      try {
        window.dispatchEvent(new CustomEvent('palstory:' + eventName, {
          detail: detail || {}
        }));
      } catch (e) {
        console.error('[PalStory.Events] Failed to dispatch:', eventName, e);
      }
    },
    
    /**
     * Listen for a custom game event
     * @param {string} eventName - Name of the event
     * @param {Function} handler - Event handler function
     */
    on: function(eventName, handler) {
      window.addEventListener('palstory:' + eventName, function(e) {
        try {
          handler(e.detail);
        } catch (err) {
          console.error('[PalStory.Events] Handler error for:', eventName, err);
        }
      });
    },
    
    // Pre-defined event names
    NAMES: {
      GAME_START: 'game_start',
      GAME_OVER: 'game_over',
      MOVE_START: 'move_start',
      MOVE_COMPLETE: 'move_complete',
      COMBAT_START: 'combat_start',
      COMBAT_END: 'combat_end',
      LEVEL_UP: 'level_up',
      ITEM_PICKUP: 'item_pickup',
      API_ERROR: 'api_error',
      SHOW_TOAST: 'show_toast'
    }
  };

  // ============================================================================
  // REGISTRY (for components, locations, monsters)
  // ============================================================================
  
  /**
   * @namespace PalStory.Registry
   * @description Central registry for game objects
   */
  PalStory.Registry = {
    locations: {},      // key: "x,y" -> component
    monsters: {},       // key: id -> monster data
    players: {},        // key: name -> player data
    items: {},          // key: id -> item data
    skills: {},         // key: skill_id -> skill definition
    
    /**
     * Get location by coordinates
     * @param {number} x 
     * @param {number} y 
     * @returns {Object|null}
     */
    getLocation: function(x, y) {
      return this.locations[x + ',' + y] || null;
    },
    
    /**
     * Set location
     * @param {number} x 
     * @param {number} y 
     * @param {Object} component 
     */
    setLocation: function(x, y, component) {
      this.locations[x + ',' + y] = component;
    },
    
    /**
     * Clear all registries
     */
    clear: function() {
      this.locations = {};
      this.monsters = {};
      this.players = {};
      this.items = {};
    }
  };

  // ============================================================================
  // LEGACY COMPATIBILITY LAYER
  // ============================================================================
  
  /**
   * Bridge old global variables to new namespace
   * This allows gradual migration without breaking existing code
   */
  function setupLegacyBridge() {
    // Map legacy globals to namespace (read-only getters where possible)
    Object.defineProperty(window, 'player_x', {
      get: function() { return PalStory.State.player.x; },
      set: function(v) { PalStory.State.player.x = v; },
      configurable: true
    });
    
    Object.defineProperty(window, 'player_y', {
      get: function() { return PalStory.State.player.y; },
      set: function(v) { PalStory.State.player.y = v; },
      configurable: true
    });
    
    Object.defineProperty(window, 'gameStarted', {
      get: function() { return PalStory.State.flags.gameStarted; },
      set: function(v) { PalStory.State.flags.gameStarted = v; },
      configurable: true
    });
    
    Object.defineProperty(window, 'canMove', {
      get: function() { return PalStory.State.flags.canMove; },
      set: function(v) { PalStory.State.flags.canMove = v; },
      configurable: true
    });
    
    Object.defineProperty(window, 'locationsLoaded', {
      get: function() { return PalStory.State.flags.locationsLoaded; },
      set: function(v) { PalStory.State.flags.locationsLoaded = v; },
      configurable: true
    });
  }
  
  // Don't set up legacy bridge by default - it can cause issues with existing code
  // Uncomment when ready for migration:
  // setupLegacyBridge();
  
  // Export setup function for manual activation
  PalStory.setupLegacyBridge = setupLegacyBridge;

  // ============================================================================
  // INITIALIZATION
  // ============================================================================
  
  console.log('[PalStory] Namespace initialized');
  
})();
