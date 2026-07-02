=== Talking Picture ===
Contributors: talkingpicture
Tags: interactive image, hotspots, tooltips, image map, education
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create interactive "Talking Pictures": drop microphone hotspots with tooltips onto an image, keep them all in a reusable library, and embed any one with a shortcode.

== Description ==

Talking Picture turns any image from your Media Library into an interactive graphic. Drop numbered microphone nodes onto the picture, give each one a tooltip (a fact, a question, a prompt), and the nodes connect in order to suggest a reading path.

Everything is saved as a reusable entry in a "Talking Pictures" library, so you keep a running record of the topics you have covered and can reopen any picture later to change the background, edit a tooltip, or move a node.

= Features =

* **Media Library backgrounds** — pick an existing image or upload a new one from within the editor; no re-uploading the same file.
* **Reusable library** — every Talking Picture is a saved post you can title, search, and revisit. The list view shows a thumbnail and the ready-to-copy shortcode.
* **Full re-editing** — change the background image, opacity, tooltip text, and node positions at any time.
* **Embed with a shortcode** — `[talking_picture id="123"]` drops the interactive picture into any post, page, or widget.
* **Responsive & fullscreen** — the rendered picture scales to its container and has a fullscreen button for presentations.
* **No external dependencies** — icons are inline SVG; nothing is loaded from a third-party CDN.

== Installation ==

1. Upload the `talking-picture` folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload.
2. Activate the plugin through the Plugins screen.
3. Open **Talking Pictures → Add New**, give it a title, select a background, and drop your nodes.
4. Copy the shortcode shown on the list screen (or build it as `[talking_picture id="POST_ID"]`) and paste it into any post or page.

== Usage ==

In the editor:

* Click the image to drop a microphone node.
* Drag a node to reposition it.
* Click a node to edit its tooltip / question or delete it.
* Nodes connect automatically in the order you place them.
* Click **Update** to save. Your Talking Picture stays in the library for reuse.

== Changelog ==

= 1.0.0 =
* Initial release: Media Library backgrounds, reusable library (custom post type), re-editable nodes and tooltips, and the `[talking_picture]` shortcode.
