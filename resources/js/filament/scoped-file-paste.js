/*
 * Scopes pasting a clipboard image to one upload.
 *
 * FilePond's own paste support listens once, on the document, and hands the
 * file to every upload on the page — so pasting a logo into one of two uploads
 * fills both. This runs on the capture phase, ahead of FilePond's handler, and
 * stops it: the image goes to the upload the admin aimed at — the one holding
 * focus, the one under the pointer, or the one last clicked — and to no other.
 *
 * With a single upload there is nothing to aim at, so it takes the paste. With
 * several and none aimed, the paste is dropped rather than filling them all. A
 * paste aimed at a text field or a rich editor is left alone, so pasting an
 * image there still behaves as that control expects, and a page with no upload
 * is never touched.
 */
(() => {
    const UPLOAD = ".filepond--root";

    let lastClicked = null;

    const aimedUpload = () => {
        const active = document.activeElement;

        return (
            active?.closest?.(UPLOAD) ??
            document.querySelector(`${UPLOAD}:hover`) ??
            (lastClicked?.isConnected ? lastClicked : null)
        );
    };

    // The upload the admin last clicked, so a paste with the pointer elsewhere
    // still lands on the one they chose.
    document.addEventListener(
        "pointerdown",
        (event) => {
            const upload = event.target.closest?.(UPLOAD);

            if (upload) {
                lastClicked = upload;
            }
        },
        true,
    );

    document.addEventListener(
        "paste",
        (event) => {
            const files = Array.from(event.clipboardData?.files ?? []);

            if (files.length === 0) {
                return;
            }

            const uploads = document.querySelectorAll(UPLOAD);

            if (uploads.length === 0) {
                return;
            }

            // A focused text field or editor owns its own paste.
            const active = document.activeElement;

            const isTyping =
                active &&
                (active.matches("input, textarea, [contenteditable]") ||
                    active.isContentEditable);

            if (isTyping && !active.closest(UPLOAD)) {
                return;
            }

            const upload =
                aimedUpload() ?? (uploads.length === 1 ? uploads[0] : null);

            // Past this point the paste is ours, so FilePond's all-uploads
            // handler is stopped even when no upload was aimed at.
            event.preventDefault();
            event.stopImmediatePropagation();

            if (!upload) {
                return;
            }

            // FilePond.find resolves an instance from its root element, which
            // is the `.filepond--root` the upload is.
            const pond = window.FilePond?.find(upload);

            pond?.addFiles(files);
        },
        true,
    );
})();
