// Fog-of-War: ONLY affect outside of visited tiles
// Implementation: draw a full-screen black overlay, then cut holes exactly over
// currently rendered location components. No blur, no player override.
(function(){
  'use strict';
  var Fog = {
    // Discovery fade state per tile key "x,y": { p: 0..1 }
    _fade: {},
    _lastTs: 0,

    // Convert tile (map) coords to current screen-space rect
    _tileToScreenRect: function(tx, ty){
      try {
        var ss = window.ss || 64;
        var px = (window.player && window.player.x) || (window.w||0)/2;
        var py = (window.player && window.player.y) || (window.h||0)/2;
        var dx = (tx - (window.player_x|0)) * ss;
        var dy = (ty - (window.player_y|0)) * ss;
        return { x: Math.round(px + dx), y: Math.round(py + dy), w: ss, h: ss };
      } catch(_){ return null; }
    },

    // Gather discovered tile keys from locationsDict
    _getDiscoveredKeys: function(){
      var out = [];
      try {
        if (window.locationsDict) {
          for (var k in window.locationsDict) { if (Object.prototype.hasOwnProperty.call(window.locationsDict, k)) out.push(k); }
        }
      } catch(_){ }
      return out;
    },
    render: function(ctx){
      try {
        ctx = ctx || (window.myGameArea && window.myGameArea.context);
        if (!ctx) return;
        var canvas = ctx.canvas; if (!canvas) return;
        var W = canvas.width, H = canvas.height;

        // Compute dt for smooth fade independent of frame rate
        var now = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
        if (!this._lastTs) this._lastTs = now;
        var dt = Math.min(0.1, Math.max(0.0, (now - this._lastTs) / 1000));
        this._lastTs = now;

        // Prepare/reuse offscreen mask canvas
        if (!this._mask || this._mask.width !== W || this._mask.height !== H) {
          this._mask = document.createElement('canvas');
          this._mask.width = W; this._mask.height = H;
        }
        var mctx = this._mask.getContext('2d');

        // Reset and copy transform from main ctx (important for DPR scaling alignment)
        mctx.setTransform(1,0,0,1,0,0);
        mctx.clearRect(0,0,W,H);
        try {
          var tr = ctx.getTransform ? ctx.getTransform() : null;
          if (tr) mctx.setTransform(tr.a, tr.b, tr.c, tr.d, tr.e, tr.f);
        } catch(_){ }

        // Draw solid black on mask
        mctx.save();
        mctx.fillStyle = 'rgba(0,0,0,1)';
        mctx.fillRect(0, 0, W, H);

        // Punch holes for visited tiles with fade-in
        mctx.globalCompositeOperation = 'destination-out';
        mctx.shadowBlur = 0; mctx.shadowOffsetX = 0; mctx.shadowOffsetY = 0;
        var keys = this._getDiscoveredKeys();
        var ss = window.ss || 64;
        var i, k;
        // Update fade progress for all discovered tiles
        var speed = 3.0; // seconds to fully reveal ~ 1/3s
        for (i = 0; i < keys.length; i++) {
          k = keys[i];
          var st = this._fade[k]; if (!st) { st = { p: 0 }; this._fade[k] = st; }
          st.p = Math.min(1, st.p + speed * dt);
        }
        // Draw reveals aligned to actually rendered locations to avoid jitter during movement
        var locs = Array.isArray(window.locations) ? window.locations : [];
        for (i = 0; i < locs.length; i++) {
          var loc = locs[i]; if (!loc || typeof loc.x !== 'number') continue;
          // Derive tile key for fade progress from current loc vs player
          var px = (window.player && window.player.x) || (window.w||0)/2;
          var py = (window.player && window.player.y) || (window.h||0)/2;
          var tx = (window.player_x|0) + Math.round((loc.x - px) / ss);
          var ty = (window.player_y|0) + Math.round((loc.y - py) / ss);
          var key = tx + ',' + ty;
          var st2 = this._fade[key];
          var alpha = st2 ? st2.p : 1;
          if (alpha <= 0) continue;
          // Quick cull in screen space using loc rect
          var lx = loc.x|0, ly = loc.y|0, lw = (loc.width|0)||ss, lh = (loc.height|0)||ss;
          if (lx > W || ly > H || lx + lw < 0 || ly + lh < 0) continue;
          mctx.globalAlpha = alpha;
          mctx.fillRect(lx, ly, lw, lh);
        }
        mctx.globalAlpha = 1;
        mctx.restore();

        // Blit mask onto main canvas on top of everything
        ctx.save();
        // Ensure main ctx uses its current transform for consistency (it already does in game loop)
        ctx.setTransform(1,0,0,1,0,0); // draw overlay in raw pixel space
        ctx.drawImage(this._mask, 0, 0);
        ctx.restore();
      } catch(_){ }
    }
  };

  window.Fog = Fog;
})();
