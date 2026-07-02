/**
 * Talking Picture — front-end renderer.
 *
 * Scans the page for every [talking_picture] output (.tp-render) and builds
 * an interactive, read-only view from the JSON payload embedded in each.
 * Percentage-based positioning keeps it responsive inside any theme column.
 */
(function () {
	"use strict";

	function cardinalDir(dx, dy) {
		if (Math.abs(dx) >= Math.abs(dy)) {
			return dx >= 0 ? { x: 1, y: 0 } : { x: -1, y: 0 };
		}
		return dy >= 0 ? { x: 0, y: 1 } : { x: 0, y: -1 };
	}

	function connectorPath(a, b, r) {
		var dx = b.x - a.x;
		var dy = b.y - a.y;
		var uA = cardinalDir(dx, dy);
		var uB = cardinalDir(-dx, -dy);
		var p1 = { x: a.x + uA.x * r, y: a.y + uA.y * r };
		var p2 = { x: b.x + uB.x * r, y: b.y + uB.y * r };
		var dist = Math.sqrt((p2.x - p1.x) * (p2.x - p1.x) + (p2.y - p1.y) * (p2.y - p1.y));
		var k = Math.max(18, dist * 0.4);
		var c1 = { x: p1.x + uA.x * k, y: p1.y + uA.y * k };
		var c2 = { x: p2.x + uB.x * k, y: p2.y + uB.y * k };
		return "M " + p1.x + " " + p1.y + " C " + c1.x + " " + c1.y + " " + c2.x + " " + c2.y + " " + p2.x + " " + p2.y;
	}

	function initInstance(root) {
		if (root.dataset.tpReady) {
			return;
		}
		root.dataset.tpReady = "1";

		var dataEl = root.querySelector(".tp-data");
		var micTpl = root.querySelector(".tp-mic-tpl");
		if (!dataEl) {
			return;
		}
		var DATA;
		try {
			DATA = JSON.parse(dataEl.textContent);
		} catch (e) {
			return;
		}
		var micSvg = micTpl ? micTpl.innerHTML : "";

		var wrap = root.querySelector(".tp-wrap");
		var img = root.querySelector(".tp-img");
		var svg = root.querySelector(".tp-svg");
		var fsBtn = root.querySelector(".tp-fs-btn");

		img.src = DATA.image;
		img.style.opacity = DATA.opacity;

		var activeTip = null;
		var activeNode = null;

		function nodeRadius() {
			var el = wrap.querySelector(".tp-node");
			return el ? el.offsetWidth / 2 : 24;
		}

		function positionTip(tip, n) {
			tip.style.display = "block";
			var W = wrap.clientWidth;
			var H = wrap.clientHeight;
			var r = nodeRadius();
			var cx = (n.xPct / 100) * W;
			var cy = (n.yPct / 100) * H;
			var tw = tip.offsetWidth;
			var th = tip.offsetHeight;
			var m = 8;
			var left = Math.max(m, Math.min(cx - tw / 2, W - tw - m));
			tip.style.left = left + "px";
			var arrowX = Math.max(12, Math.min(cx - left, tw - 12));
			tip.style.setProperty("--tp-arrow-x", arrowX + "px");
			if (cy - r - 10 - th < m) {
				tip.classList.add("below");
				tip.style.top = cy + r + 10 + "px";
			} else {
				tip.classList.remove("below");
				tip.style.top = cy - r - 10 - th + "px";
			}
		}

		function showTip(tip, n) {
			if (activeTip && activeTip !== tip) {
				hideTip();
			}
			activeTip = tip;
			activeNode = n;
			positionTip(tip, n);
		}

		function hideTip() {
			if (activeTip) {
				activeTip.style.display = "none";
				activeTip.classList.remove("below");
			}
			activeTip = null;
			activeNode = null;
		}

		(DATA.nodes || []).forEach(function (n, idx) {
			var node = document.createElement("div");
			node.className = "tp-node";
			node.style.left = n.xPct + "%";
			node.style.top = n.yPct + "%";
			node.innerHTML = micSvg + '<span class="tp-order">' + (idx + 1) + "</span>";

			if (n.text && n.text.trim().length) {
				var tip = document.createElement("div");
				tip.className = "tp-tooltip";
				tip.textContent = n.text;
				wrap.appendChild(tip);

				node.addEventListener("mouseenter", function () {
					showTip(tip, n);
				});
				node.addEventListener("mouseleave", hideTip);
				node.addEventListener("click", function (e) {
					e.stopPropagation();
					if (activeTip === tip) {
						hideTip();
					} else {
						showTip(tip, n);
					}
				});
			}
			wrap.appendChild(node);
		});

		document.addEventListener("click", hideTip);

		function draw() {
			var w = wrap.clientWidth;
			var h = wrap.clientHeight;
			if (!w || !h) {
				return;
			}
			svg.setAttribute("viewBox", "0 0 " + w + " " + h);
			svg.querySelectorAll("path.tp-line").forEach(function (p) {
				p.remove();
			});
			var r = nodeRadius();
			var pts = (DATA.nodes || []).map(function (n) {
				return { x: (n.xPct / 100) * w, y: (n.yPct / 100) * h };
			});
			for (var i = 0; i < pts.length - 1; i++) {
				var path = document.createElementNS("http://www.w3.org/2000/svg", "path");
				path.setAttribute("class", "tp-line");
				path.setAttribute("d", connectorPath(pts[i], pts[i + 1], r));
				path.setAttribute("fill", "none");
				path.setAttribute("stroke", "#5b9bf5");
				path.setAttribute("stroke-width", "3");
				path.setAttribute("stroke-linecap", "round");
				svg.appendChild(path);
			}
			if (activeTip && activeNode) {
				positionTip(activeTip, activeNode);
			}
		}

		fsBtn.addEventListener("click", function (e) {
			e.stopPropagation();
			var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
			if (fsEl) {
				(document.exitFullscreen || document.webkitExitFullscreen).call(document);
			} else {
				var req = root.requestFullscreen || root.webkitRequestFullscreen;
				if (req) {
					try {
						var p = req.call(root);
						if (p && p.catch) {
							p.catch(function () {});
						}
					} catch (err) {}
				}
			}
		});

		function onFsChange() {
			var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
			root.classList.toggle("tp-fullscreen", !!fsEl);
			hideTip();
			setTimeout(draw, 60);
		}
		document.addEventListener("fullscreenchange", onFsChange);
		document.addEventListener("webkitfullscreenchange", onFsChange);

		if (img.complete) {
			draw();
		} else {
			img.addEventListener("load", draw);
		}
		window.addEventListener("resize", draw);
	}

	function initAll() {
		document.querySelectorAll(".tp-render[data-tp]").forEach(initInstance);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", initAll);
	} else {
		initAll();
	}
})();
