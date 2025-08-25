// Assets module: image caching, queued loading, and helpers
// Exposes window.Assets.{loadImage, loadImageQueued, evictIfNeeded, getPinnedImageUrls, preloadImages}
// and global proxies loadImage/loadImageQueued/getPinnedImageUrls/evictIfNeeded/preloadImages for legacy code.
(function(){
  'use strict';
  if (!window.Assets) window.Assets = {};

  var imageCache = {};
  var imageCacheMeta = {}; // url -> { lastAccess: number }
  var MAX_CACHE_IMAGES = 150;

  var MAX_PARALLEL_IMAGE_LOADS = 4;
  var __imgQueue = [];
  var __imgLoadsInFlight = 0;

  function __processImageQueue() {
    while (__imgLoadsInFlight < MAX_PARALLEL_IMAGE_LOADS && __imgQueue.length > 0) {
      var task = __imgQueue.shift();
      __imgLoadsInFlight++;
      loadImage(task.url).then(function (img) {
        __imgLoadsInFlight--;
        try { task.resolve(img); } finally { __processImageQueue(); }
      }).catch(function () {
        __imgLoadsInFlight--;
        try { task.resolve(null); } finally { __processImageQueue(); }
      });
    }
  }

  function loadImageQueued(url) {
    return new Promise(function (resolve) {
      if (url && imageCache[url]) {
        imageCacheMeta[url] = { lastAccess: Date.now() };
        resolve(imageCache[url]);
        return;
      }
      __imgQueue.push({ url: url, resolve: resolve });
      __processImageQueue();
    });
  }

  function loadImage(url) {
    return new Promise(function (resolve) {
      if (!url) return resolve(null);
      if (imageCache[url]) { imageCacheMeta[url] = { lastAccess: Date.now() }; return resolve(imageCache[url]); }
      var img = new Image();
      img.onload = function () { imageCache[url] = img; imageCacheMeta[url] = { lastAccess: Date.now() }; evictIfNeeded(); resolve(img); };
      img.onerror = function () {
        try {
          var placeholder = (typeof window.getImageUrl === 'function') ? window.getImageUrl('placeholder.png') : null;
          if (placeholder && url !== placeholder) {
            loadImage(placeholder).then(function (pimg) {
              imageCache[url] = pimg || null;
              if (pimg) { imageCacheMeta[url] = { lastAccess: Date.now() }; }
              evictIfNeeded();
              resolve(pimg || null);
            }).catch(function(){ resolve(null); });
            return;
          }
        } catch (_) {}
        resolve(null);
      };
      img.src = url;
    });
  }

  function getPinnedImageUrls() {
    var pinned = new Set();
    try {
      var px = window.player_x, py = window.player_y;
      var coords = [ [px, py], [px+1, py], [px-1, py], [px, py+1], [px, py-1] ];
      for (var i = 0; i < coords.length; i++) {
        var key = '' + coords[i][0] + ',' + coords[i][1];
        var loc = window.locationsDict && window.locationsDict[key];
        if (loc && loc.image && loc.image.currentSrc) pinned.add(loc.image.currentSrc);
      }
    } catch (_) { }
    try { if (window.currentMonster && window.currentMonster.image) pinned.add(window.currentMonster.image); } catch (_) {}
    try { if (window.player && window.player.image && window.player.image.currentSrc) pinned.add(window.player.image.currentSrc); } catch (_) {}
    return pinned;
  }

  function evictIfNeeded() {
    try {
      var keys = Object.keys(imageCache);
      if (keys.length <= MAX_CACHE_IMAGES) return;
      var pinned = getPinnedImageUrls();
      keys.sort(function(a,b){ return (imageCacheMeta[a]?.lastAccess||0) - (imageCacheMeta[b]?.lastAccess||0); });
      for (var i = 0; i < keys.length - MAX_CACHE_IMAGES; i++) {
        var k = keys[i];
        if (pinned.has(k)) continue; // don't evict pinned
        delete imageCache[k];
        delete imageCacheMeta[k];
      }
    } catch (_) { }
  }

  function preloadImages(urls) {
    try {
      var list = (urls || []).filter(Boolean).map(loadImageQueued);
      return Promise.all(list);
    } catch (_) {
      return Promise.resolve([]);
    }
  }

  // Expose
  window.Assets.loadImage = loadImage;
  window.Assets.loadImageQueued = loadImageQueued;
  window.Assets.getPinnedImageUrls = getPinnedImageUrls;
  window.Assets.evictIfNeeded = evictIfNeeded;
  window.Assets.preloadImages = preloadImages;

  // Global proxies for legacy code
  if (typeof window.loadImage !== 'function') window.loadImage = loadImage;
  if (typeof window.loadImageQueued !== 'function') window.loadImageQueued = loadImageQueued;
  if (typeof window.getPinnedImageUrls !== 'function') window.getPinnedImageUrls = getPinnedImageUrls;
  if (typeof window.evictIfNeeded !== 'function') window.evictIfNeeded = evictIfNeeded;
  if (typeof window.preloadImages !== 'function') window.preloadImages = preloadImages;
})();
