(function () {
  'use strict';
  
  // ============================================================================
  // API Layer - Centralized server communication with error handling
  // ============================================================================
  
  var BASE_URL = 'gameServer.php';
  var REQUEST_TIMEOUT = 15000; // 15 seconds
  var MAX_RETRIES = 2;
  var RETRY_DELAY = 1000; // 1 second
  
  // Error types for structured error handling
  var ErrorTypes = {
    NETWORK: 'network_error',
    TIMEOUT: 'timeout',
    SERVER: 'server_error',
    PARSE: 'parse_error',
    RATE_LIMITED: 'rate_limited',
    UNKNOWN: 'unknown_error'
  };
  
  // Track pending requests for debugging
  var pendingRequests = 0;
  
  /**
   * Centralized error logger
   * @param {string} context - Where the error occurred
   * @param {*} error - The error object or message
   * @param {Object} [extra] - Additional context
   */
  function logError(context, error, extra) {
    var msg = '[API Error] ' + context + ': ';
    if (error && error.message) {
      msg += error.message;
    } else if (typeof error === 'string') {
      msg += error;
    } else {
      msg += JSON.stringify(error);
    }
    console.error(msg, extra || '');
    
    // Optionally dispatch event for UI to show error
    try {
      window.dispatchEvent(new CustomEvent('palstory:api_error', {
        detail: { context: context, error: error, extra: extra }
      }));
    } catch (_) {}
  }
  
  /**
   * Classify error type from jQuery AJAX error
   * @param {Object} http - jQuery XHR object
   * @param {string} status - Status string
   * @param {string} error - Error message
   * @returns {Object} Structured error object
   */
  function classifyError(http, status, error) {
    var result = {
      type: ErrorTypes.UNKNOWN,
      message: error || status || 'Unknown error',
      status: http ? http.status : 0,
      recoverable: true
    };
    
    if (status === 'timeout') {
      result.type = ErrorTypes.TIMEOUT;
      result.message = 'Request timed out';
    } else if (status === 'parsererror') {
      result.type = ErrorTypes.PARSE;
      result.message = 'Invalid response from server';
      result.recoverable = false;
    } else if (!http || http.status === 0) {
      result.type = ErrorTypes.NETWORK;
      result.message = 'Network error - check your connection';
    } else if (http.status === 429) {
      result.type = ErrorTypes.RATE_LIMITED;
      result.message = 'Too many requests - please wait';
      result.recoverable = true;
    } else if (http.status >= 500) {
      result.type = ErrorTypes.SERVER;
      result.message = 'Server error (' + http.status + ')';
    }
    
    return result;
  }
  
  /**
   * Make an API request with retry logic and error handling
   * @param {Object} params - Request parameters
   * @param {Object} [options] - Request options
   * @returns {Promise} Resolves with response data
   */
  function _req(params, options) {
    options = options || {};
    var retries = options.retries || 0;
    var maxRetries = options.maxRetries || MAX_RETRIES;
    
    return new Promise(function (resolve, reject) {
      pendingRequests++;
      
      $.ajax({
        url: BASE_URL,
        type: options.method || 'get',
        data: params,
        dataType: options.dataType || 'json',
        timeout: options.timeout || REQUEST_TIMEOUT,
        success: function (resp) {
          pendingRequests--;
          
          // Check for server-side error responses
          if (resp && resp.err) {
            logError('Server returned error', resp.err, params);
          }
          
          resolve(resp);
        },
        error: function (http, status, error) {
          pendingRequests--;
          
          var classifiedError = classifyError(http, status, error);
          logError('Request failed', classifiedError, { params: params, retry: retries });
          
          // Retry logic for recoverable errors
          if (classifiedError.recoverable && retries < maxRetries) {
            var delay = RETRY_DELAY * Math.pow(2, retries); // Exponential backoff
            console.log('[API] Retrying in ' + delay + 'ms (attempt ' + (retries + 1) + '/' + maxRetries + ')');
            
            setTimeout(function() {
              _req(params, Object.assign({}, options, { retries: retries + 1 }))
                .then(resolve)
                .catch(reject);
            }, delay);
            return;
          }
          
          reject(classifiedError);
        }
      });
    });
  }
  
  /**
   * Get count of pending requests (for loading indicators)
   * @returns {number}
   */
  function getPendingCount() {
    return pendingRequests;
  }

  var api = {
    // Combat
    fightMonster: function (playerName, roomId, skill) {
      var params = { fight_monster: playerName, room_id: roomId };
      if (typeof skill === 'string' && skill) params.skill = skill;
      return _req(params);
    },

    // Movement
    movePlayer: function (playerName, roomId, dir) {
      return _req({ move_player: playerName, room_id: roomId, dir: dir });
    },

    // Locations
    getAllLocations: function (roomId) {
      return _req({ get_all_locations: roomId });
    },
    getLocation: function (roomId, x, y) {
      return _req({ get_location: roomId, x: x, y: y });
    },
    getRoom: function (roomName) {
      return _req({ get_room: roomName });
    },

    // Monsters
    getMonster: function (roomId, x, y) {
      return _req({ get_monster: roomId, x: x, y: y });
    },
    
    getAllMonsters: function (roomId) {
      return _req({ get_all_monsters: roomId });
    },

    // Music
    getMusic: function () {
      return _req({ get_music: 1 });
    },

    // Players
    createPlayer: function (name, roomId, portraitId) {
      return _req({ create_player: name, room_id: roomId, player_portrait: portraitId });
    },
    getPlayer: function (name, roomId) {
      return _req({ get_player: name, room_id: roomId });
    },
    getAllPlayers: function (roomId) {
      // Use legacy key expected by backend
      return _req({ get_players: roomId });
    },

    // Items
    getItems: function (playerId, roomId) {
      return _req({ get_items: playerId, room_id: roomId });
    },
    unequipItem: function (itemId, playerId) {
      return _req({ unequip_item: itemId, player_id: playerId });
    },
    equipItem: function (itemId, playerId) {
      return _req({ equip_item: itemId, player_id: playerId });
    },
    dropItem: function (itemId, playerId) {
      return _req({ drop_item: itemId, player_id: playerId });
    },
    // Gathering
    gatherResource: function(playerName, roomId) {
      return _req({ gather_resource: playerName, room_id: roomId });
    },
    // Resting
    restAtLocation: function(playerName, roomId) {
      return _req({ rest_player: playerName, room_id: roomId });
    },
    // Respawn
    setRespawnPoint: function(playerName, roomId) {
      return _req({ set_respawn: playerName, room_id: roomId });
    },
    // Rooms
    createRoom: function (name, expiration, regen) {
      return _req({ create_room: name, expiration: expiration, regen: regen });
    },

    // Heartbeat
    pingPlayer: function(playerName, roomId) {
      return _req({ ping_player: playerName, room_id: roomId });
    },
    
    // Shop
    getShop: function(playerName, roomId) {
      return _req({ get_shop: playerName, room_id: roomId });
    },
    buyItem: function(playerName, roomId, itemId) {
      return _req({ buy_item: playerName, room_id: roomId, item_id: itemId });
    },
    sellItem: function(playerName, roomId, itemDbId) {
      return _req({ sell_item: playerName, room_id: roomId, item_db_id: itemDbId });
    },
    
    // Storage
    getStorage: function(playerName, roomId) {
      return _req({ get_storage: playerName, room_id: roomId });
    },
    depositItem: function(playerName, roomId, itemDbId) {
      return _req({ deposit_item: playerName, room_id: roomId, item_db_id: itemDbId });
    },
    withdrawItem: function(playerName, roomId, storageId) {
      return _req({ withdraw_item: playerName, room_id: roomId, storage_id: storageId });
    },
    
    // Skills & Jobs
    getSkills: function() {
      return _req({ get_skills: 1 });
    },
    getJobs: function() {
      return _req({ get_jobs: 1 });
    },
    selectJob: function(playerName, roomId, jobId) {
      return _req({ select_job: 1, player_name: playerName, room_id: roomId, job: jobId });
    },
    unlockSkill: function(playerName, roomId, skillId) {
      return _req({ unlock_skill: 1, player_name: playerName, room_id: roomId, skill: skillId });
    },
    
    // Utility methods
    getPendingCount: getPendingCount,
    ErrorTypes: ErrorTypes
  };
  
  // ============================================================================
  // SKILL DEFINITIONS CACHE
  // ============================================================================
  
  var skillCache = null;
  var skillCachePromise = null;
  
  /**
   * Get skill definitions with caching
   * @returns {Promise<Object>} Map of skill_id -> skill definition
   */
  function getSkillDefinitions() {
    if (skillCache) {
      return Promise.resolve(skillCache);
    }
    
    if (skillCachePromise) {
      return skillCachePromise;
    }
    
    skillCachePromise = api.getSkills().then(function(skills) {
      skillCache = {};
      if (Array.isArray(skills)) {
        skills.forEach(function(skill) {
          skillCache[skill.skill_id] = skill;
        });
      }
      console.log('[API] Cached', Object.keys(skillCache).length, 'skill definitions');
      return skillCache;
    }).catch(function(err) {
      console.error('[API] Failed to fetch skill definitions:', err);
      skillCachePromise = null;
      return {};
    });
    
    return skillCachePromise;
  }
  
  /**
   * Get a specific skill definition by ID
   * @param {string} skillId - The skill ID
   * @returns {Promise<Object|null>} The skill definition or null
   */
  function getSkillById(skillId) {
    return getSkillDefinitions().then(function(cache) {
      return cache[skillId] || null;
    });
  }
  
  /**
   * Clear the skill cache (e.g., after admin changes)
   */
  function clearSkillCache() {
    skillCache = null;
    skillCachePromise = null;
  }
  
  // Export skill cache functions
  api.getSkillDefinitions = getSkillDefinitions;
  api.getSkillById = getSkillById;
  api.clearSkillCache = clearSkillCache;

  window.api = api;
  
  // Listen for API errors and show user-friendly messages
  window.addEventListener('palstory:api_error', function(e) {
    try {
      var detail = e.detail || {};
      var error = detail.error || {};
      
      // Only show toast for non-recoverable or final errors
      if (!error.recoverable || (detail.extra && detail.extra.retry >= MAX_RETRIES - 1)) {
        // Dispatch event for UI to show toast notification
        window.dispatchEvent(new CustomEvent('palstory:show_toast', {
          detail: { message: error.message || 'An error occurred', type: 'error' }
        }));
      }
    } catch (_) {}
  });
})();
