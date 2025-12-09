// Audio module (safe to load before game.js)
(function(){
  'use strict';
  var localAudioCache = {};

  function preloadAudio(urls) {
    try {
      (urls || []).forEach(function (u) {
        if (!u) return;
        if (localAudioCache[u]) return;
        var a = new Audio(u);
        a.preload = 'auto';
        a.loop = false;
        localAudioCache[u] = a;
      });
    } catch (e) { /* ignore */ }
  }

  function playSound(url) {
    try {
      var sfx = (typeof window.sfx === 'number') ? window.sfx : 1;
      if (sfx !== 1) return;
      var src = url;
      var audio;
      if (localAudioCache[src]) {
        audio = localAudioCache[src].cloneNode();
      } else {
        audio = new Audio(src);
        audio.preload = 'auto';
      }
      audio.loop = false;
      var p = audio.play();
      if (p && typeof p.catch === 'function') { p.catch(function(){/* ignore */}); }
    } catch (e) { /* ignore */ }
  }

  if (typeof window.preloadAudio !== 'function') {
    window.preloadAudio = preloadAudio;
  }
  if (typeof window.playSound !== 'function') {
    window.playSound = playSound;
  }

  // High-level audio controller
  if (!window.AudioCtl) window.AudioCtl = {};
  window.AudioCtl.getMusic = function (setBgm) {
    // mirror legacy getMusic behavior
    try { window.localStorage.setItem('palstory-bgm', String(setBgm ? 1 : 0)); } catch (e) {}
    if (setBgm == 1) {
      window.bgm = 1;
      if (typeof showEl === 'function') { showEl('#bgmOffBtn'); }
      if (typeof hideEl === 'function') { hideEl('#bgmOnBtn'); }
      console.log('getting music..');
      if (window.api && typeof window.api.getMusic === 'function') {
        window.api.getMusic()
          .then(function (response) {
            if (response && response.length > 0) {
              var e = document.getElementById('bgm');
              if (e !== null) e.remove();
              var s = document.createElement('audio');
              s.setAttribute('id', 'bgm');
              s.src = response[2 + Math.floor(Math.random() * (response.length - 2))];
              s.setAttribute('preload', 'auto');
              s.setAttribute('controls', 'loop');
              s.style.display = 'none';
              document.body.appendChild(s);
              // Apply preferred volume if stored
              try {
                var v = parseFloat(localStorage.getItem('palstory-bgm-vol'));
                if (!isNaN(v)) s.volume = Math.min(1, Math.max(0, v));
              } catch (_) {}
              if (s.play) { var p = s.play(); if (p && p.catch) p.catch(function(){}); }
            }
          })
          .catch(function (err) { console.error('error: ' + err); });
      }
    } else {
      window.bgm = 0;
      if (typeof hideEl === 'function') { hideEl('#bgmOffBtn'); }
      if (typeof showEl === 'function') { showEl('#bgmOnBtn'); }
      var e2 = document.getElementById('bgm');
      if (e2 !== null && e2.pause) e2.pause();
    }
  };

  window.AudioCtl.getSfx = function (setSfx) {
    try { window.localStorage.setItem('palstory-sfx', String(setSfx ? 1 : 0)); } catch (e) {}
    if (setSfx == 1) {
      window.sfx = 1;
      if (typeof hideEl === 'function') { hideEl('#sfxOnBtn'); }
      if (typeof showEl === 'function') { showEl('#sfxOffBtn'); }
    } else {
      window.sfx = 0;
      if (typeof hideEl === 'function') { hideEl('#sfxOffBtn'); }
      if (typeof showEl === 'function') { showEl('#sfxOnBtn'); }
    }
  };

  window.AudioCtl.getT2s = function (setT2s) {
    if (setT2s == 1) {
      window.t2s = 1;
      if (typeof hideEl === 'function') { hideEl('#t2sOnBtn'); }
      if (typeof showEl === 'function') { showEl('#t2sOffBtn'); }
    } else {
      window.t2s = 0;
      try { if (window.speechSynthesis) window.speechSynthesis.cancel(); } catch (e) {}
      if (typeof hideEl === 'function') { hideEl('#t2sOffBtn'); }
      if (typeof showEl === 'function') { showEl('#t2sOnBtn'); }
    }
  };

  // Initialize audio preferences from localStorage without playing sounds
  window.AudioCtl.initPreferences = function () {
    try {
      var sfxPref = localStorage.getItem('palstory-sfx');
      if (sfxPref === '0') {
        window.sfx = 0;
        if (typeof hideEl === 'function') { hideEl('#sfxOffBtn'); }
        if (typeof showEl === 'function') { showEl('#sfxOnBtn'); }
      } else {
        window.sfx = 1;
        if (typeof hideEl === 'function') { hideEl('#sfxOnBtn'); }
        if (typeof showEl === 'function') { showEl('#sfxOffBtn'); }
      }

      var bgmPref = localStorage.getItem('palstory-bgm');
      if (bgmPref === '1') {
        window.bgm = 1;
        if (typeof hideEl === 'function') { hideEl('#bgmOnBtn'); }
        if (typeof showEl === 'function') { showEl('#bgmOffBtn'); }
        if (typeof window.AudioCtl.setupBgmUnlockOnce === 'function') window.AudioCtl.setupBgmUnlockOnce();
      } else {
        window.bgm = 0;
        if (typeof hideEl === 'function') { hideEl('#bgmOffBtn'); }
        if (typeof showEl === 'function') { showEl('#bgmOnBtn'); }
      }
    } catch (e) { /* ignore */ }
  };

  // After first user gesture, start BGM if enabled (autoplay policy safe)
  window.AudioCtl.setupBgmUnlockOnce = function () {
    if (window.__bgmUnlockSetup) return;
    window.__bgmUnlockSetup = true;
    var handler = function () {
      try {
        if (window.bgm === 1) {
          var el = document.getElementById('bgm');
          if (!el || el.paused) {
            if (window.AudioCtl && typeof window.AudioCtl.getMusic === 'function') window.AudioCtl.getMusic(1);
          }
        }
      } catch (_) { }
      window.removeEventListener('pointerdown', handler);
      window.removeEventListener('click', handler);
      window.removeEventListener('keydown', handler);
      window.removeEventListener('touchstart', handler);
    };
    window.addEventListener('pointerdown', handler, { once: true, passive: true });
    window.addEventListener('click', handler, { once: true, passive: true });
    window.addEventListener('keydown', handler, { once: true });
    window.addEventListener('touchstart', handler, { once: true, passive: true });
  };

  // Global TTS helper for legacy callers (reads window.t2s)
  if (typeof window.speak !== 'function') {
    window.speak = function (message) {
      try { window.speechSynthesis.cancel(); } catch (_) {}
      try {
        if (window.t2s === 1) {
          var msg = new SpeechSynthesisUtterance(message);
          var voices = [];
          try { voices = window.speechSynthesis.getVoices() || []; } catch (_) {}
          if (voices && voices.length) msg.voice = voices[0];
          try { window.speechSynthesis.speak(msg); } catch (_) {}
        }
      } catch (_) {}
    };
  }
})();
