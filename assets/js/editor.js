/**
 * Talking Picture — admin canvas editor.
 *
 * Ports the standalone editor to WordPress: the background comes from the
 * Media Library, and state is mirrored into hidden form fields so it saves
 * with the post. No export step — the picture lives in the library.
 */
(function () {
	"use strict";

	var root = document.getElementById("tp-editor");
	if (!root) {
		return;
	}

	var nodeSeq = 0; // Monotonic id source for nodes in this editor session.

	// ---- Elements ----
	var pickBtn = document.getElementById("tpPickImage");
	var bgImage = document.getElementById("tpBg");
	var opacitySlider = document.getElementById("tpOpacity");
	var opacityVal = document.getElementById("tpOpacityVal");
	var canvasWrap = document.getElementById("tpCanvasWrap");
	var canvasContainer = document.getElementById("tpCanvasContainer");
	var emptyState = document.getElementById("tpEmpty");
	var linesSvg = document.getElementById("tpLines");
	var nodeCountEl = document.getElementById("tpNodeCount");
	var popover = document.getElementById("tpPopover");
	var popoverText = document.getElementById("tpPopoverText");
	var saveNodeBtn = document.getElementById("tpSaveNode");
	var deleteNodeBtn = document.getElementById("tpDeleteNode");

	// Hidden form fields.
	var fieldImageId = document.getElementById("tp_image_id");
	var fieldOpacity = document.getElementById("tp_opacity");
	var fieldNodes = document.getElementById("tp_nodes");

	var micSvg = (document.getElementById("tpMicSvg") || {}).innerHTML || "";

	// ---- State (restored from data-* attributes) ----
	var state = {
		imageId: parseInt(root.getAttribute("data-image-id"), 10) || 0,
		imageUrl: root.getAttribute("data-image-url") || "",
		opacity: parseFloat(root.getAttribute("data-opacity")),
		nodes: [],
	};
	if (isNaN(state.opacity)) {
		state.opacity = 0.7;
	}
	try {
		var parsed = JSON.parse(root.getAttribute("data-nodes") || "[]");
		if (Array.isArray(parsed)) {
			state.nodes = parsed.map(function (n) {
				return {
					id: ++nodeSeq,
					xPct: clamp(parseFloat(n.xPct) || 0),
					yPct: clamp(parseFloat(n.yPct) || 0),
					text: n.text || "",
				};
			});
		}
	} catch (e) {
		state.nodes = [];
	}

	var activeNodeId = null;
	var suppressCanvasClick = false;

	function clamp(v) {
		return Math.max(0, Math.min(100, v));
	}

	// ---- Sync state into hidden fields (called after every change) ----
	function sync() {
		fieldImageId.value = state.imageId || 0;
		fieldOpacity.value = state.opacity;
		fieldNodes.value = JSON.stringify(
			state.nodes.map(function (n) {
				return { xPct: n.xPct, yPct: n.yPct, text: n.text || "" };
			})
		);
	}

	// ---- Media Library picker ----
	var frame = null;
	pickBtn.addEventListener("click", function (e) {
		e.preventDefault();
		if (frame) {
			frame.open();
			return;
		}
		frame = wp.media({
			title: "Select background image",
			button: { text: "Use this image" },
			library: { type: "image" },
			multiple: false,
		});
		frame.on("select", function () {
			var att = frame.state().get("selection").first().toJSON();
			state.imageId = att.id;
			state.imageUrl = att.url;
			applyImage();
			sync();
		});
		frame.open();
	});

	function applyImage() {
		if (!state.imageUrl) {
			return;
		}
		bgImage.src = state.imageUrl;
		bgImage.style.opacity = state.opacity;
		emptyState.hidden = true;
		canvasContainer.hidden = false;
	}

	bgImage.addEventListener("load", redraw);

	// ---- Opacity ----
	opacitySlider.addEventListener("input", function () {
		state.opacity = opacitySlider.value / 100;
		opacityVal.textContent = opacitySlider.value + "%";
		bgImage.style.opacity = state.opacity;
		sync();
	});

	// ---- Placing nodes ----
	canvasWrap.addEventListener("click", function (e) {
		if (suppressCanvasClick) {
			suppressCanvasClick = false;
			return;
		}
		if (e.target.closest(".tp-node")) {
			return;
		}
		if (!state.imageUrl) {
			return;
		}
		var rect = canvasWrap.getBoundingClientRect();
		var xPct = ((e.clientX - rect.left) / rect.width) * 100;
		var yPct = ((e.clientY - rect.top) / rect.height) * 100;
		var node = {
			id: ++nodeSeq,
			xPct: clamp(xPct),
			yPct: clamp(yPct),
			text: "",
		};
		state.nodes.push(node);
		renderNodes();
		redraw();
		sync();
		openPopover(node.id);
	});

	// ---- Render node markers ----
	function renderNodes() {
		canvasWrap.querySelectorAll(".tp-node").forEach(function (n) {
			n.remove();
		});
		state.nodes.forEach(function (node, idx) {
			var el = document.createElement("div");
			el.className = "tp-node";
			el.dataset.id = node.id;
			el.style.left = node.xPct + "%";
			el.style.top = node.yPct + "%";
			el.innerHTML = micSvg + '<span class="tp-order">' + (idx + 1) + "</span>";
			attachNodeHandlers(el, node);
			canvasWrap.appendChild(el);
		});
		nodeCountEl.textContent = state.nodes.length;
	}

	// ---- Node drag + click ----
	function attachNodeHandlers(el, node) {
		var dragging = false;
		var moved = false;
		var startX = 0;
		var startY = 0;

		el.addEventListener("pointerdown", function (e) {
			e.stopPropagation();
			dragging = true;
			moved = false;
			startX = e.clientX;
			startY = e.clientY;
			el.setPointerCapture(e.pointerId);
			el.classList.add("dragging");
		});

		el.addEventListener("pointermove", function (e) {
			if (!dragging) {
				return;
			}
			if (Math.abs(e.clientX - startX) > 3 || Math.abs(e.clientY - startY) > 3) {
				moved = true;
			}
			var rect = canvasWrap.getBoundingClientRect();
			node.xPct = clamp(((e.clientX - rect.left) / rect.width) * 100);
			node.yPct = clamp(((e.clientY - rect.top) / rect.height) * 100);
			el.style.left = node.xPct + "%";
			el.style.top = node.yPct + "%";
			redraw();
		});

		el.addEventListener("pointerup", function (e) {
			if (!dragging) {
				return;
			}
			dragging = false;
			el.classList.remove("dragging");
			el.releasePointerCapture(e.pointerId);
			if (moved) {
				suppressCanvasClick = true;
				sync();
			} else {
				openPopover(node.id);
			}
		});
	}

	// ---- Popover editing ----
	function openPopover(id) {
		activeNodeId = id;
		var node = state.nodes.find(function (n) {
			return n.id === id;
		});
		if (!node) {
			return;
		}
		popoverText.value = node.text || "";

		var el = canvasWrap.querySelector('.tp-node[data-id="' + id + '"]');
		if (!el) {
			return;
		}
		var nodeRect = el.getBoundingClientRect();

		popover.hidden = false;
		var top = window.scrollY + nodeRect.top - popover.offsetHeight - 14;
		var left = window.scrollX + nodeRect.left + nodeRect.width / 2 - 20;
		popover.style.top = Math.max(8, top) + "px";
		popover.style.left = Math.max(8, left) + "px";
		popoverText.focus();
	}

	function closePopover() {
		popover.hidden = true;
		activeNodeId = null;
	}

	popoverText.addEventListener("input", function () {
		var node = state.nodes.find(function (n) {
			return n.id === activeNodeId;
		});
		if (node) {
			node.text = popoverText.value;
			sync();
		}
	});

	saveNodeBtn.addEventListener("click", closePopover);

	deleteNodeBtn.addEventListener("click", function () {
		state.nodes = state.nodes.filter(function (n) {
			return n.id !== activeNodeId;
		});
		closePopover();
		renderNodes();
		redraw();
		sync();
	});

	document.addEventListener("pointerdown", function (e) {
		if (popover.hidden) {
			return;
		}
		if (popover.contains(e.target)) {
			return;
		}
		if (e.target.closest(".tp-node")) {
			return;
		}
		closePopover();
	});

	// ---- Bezier connector drawing ----
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
		var dist = Math.hypot(p2.x - p1.x, p2.y - p1.y);
		var k = Math.max(18, dist * 0.4);
		var c1 = { x: p1.x + uA.x * k, y: p1.y + uA.y * k };
		var c2 = { x: p2.x + uB.x * k, y: p2.y + uB.y * k };
		return (
			"M " + p1.x + " " + p1.y +
			" C " + c1.x + " " + c1.y + " " + c2.x + " " + c2.y + " " + p2.x + " " + p2.y
		);
	}

	function redraw() {
		var w = canvasWrap.clientWidth;
		var h = canvasWrap.clientHeight;
		if (!w || !h) {
			return;
		}
		linesSvg.setAttribute("viewBox", "0 0 " + w + " " + h);
		linesSvg.querySelectorAll("path.tp-line").forEach(function (p) {
			p.remove();
		});

		var r = 27;
		var pts = state.nodes.map(function (n) {
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
			linesSvg.appendChild(path);
		}
	}

	window.addEventListener("resize", redraw);

	// ---- Init ----
	if (state.imageUrl) {
		applyImage();
	}
	opacityVal.textContent = Math.round(state.opacity * 100) + "%";
	renderNodes();
	sync();
})();
