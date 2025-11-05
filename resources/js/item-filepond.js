// resources/js/item-filepond.js
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginImageResize from 'filepond-plugin-image-resize';

import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginImageResize);

function initFilePondOnInput(input) {
  if (!input) return null;

  const initialUrl = input.getAttribute('data-initial-url') || '';
  const previewHeight = parseInt(input.getAttribute('data-preview-height') || '120', 10);
  const thumbWidth = parseInt(input.getAttribute('data-thumb-width') || '160', 10);

  const pond = FilePond.create(input, {
    allowMultiple: false,
    instantUpload: false,
    allowReorder: false,
    credits: false,
    allowImagePreview: true,
    // Ensure the file remains attached to the original <input> so FormData submissions include it
    storeAsFile: true,

    // Image preview sizing (height controls preview panel height)
    imagePreviewHeight: previewHeight,

    // Resize settings (keeps client preview reasonable and helps when sending)
    imageResizeTargetWidth: 1200,
    imageResizeTargetHeight: 800,
    imageResizeMode: 'cover',

    // Modern interactive placeholder text (no default image fallback)
    labelIdle: `
      <div style="text-align:left;padding:8px 12px">
        <div style="font-weight:600;color:#111827;font-size:14px">Click to upload Item Photo</div>
        <div style="font-size:12px;color:#6b7280;margin-top:3px">or
        <span style="font-weight:600">drag and drop</span> JPG/PNG, up to 2MB</div>
      </div>`,

    files: initialUrl ? [{
      source: initialUrl,
      options: { type: 'local' }
    }] : [],

    // small accessibility label
    labelFileProcessingComplete: 'Upload ready',
  });

  input._pond = pond;

  const dispatchInputChange = () => {
    try {
      input.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (error) {
      console.warn('FilePond change dispatch failed', error);
    }
  };

  const markDatasetState = (hasFile, fileItem = null) => {
    if (hasFile) {
      input.dataset.filepondHasFile = '1';
      const name = fileItem?.filename || fileItem?.file?.name || '';
      input.dataset.filepondFileName = name;
    } else {
      delete input.dataset.filepondHasFile;
      delete input.dataset.filepondFileName;
    }
  };

  pond.on('addfile', (error, fileItem) => {
    if (!error) {
      markDatasetState(true, fileItem);
      dispatchInputChange();
    }
  });

  pond.on('removefile', () => {
    markDatasetState(false);
    dispatchInputChange();
  });

  // adjust DOM for thumbnail sizing after FilePond mounts
  pond.on('addfile', () => {
    // run in next tick to ensure DOM present
    requestAnimationFrame(() => {
      document.querySelectorAll('.filepond--item').forEach(it => {
        it.style.maxWidth = `${thumbWidth}px`;
        it.style.maxHeight = `${previewHeight + 20}px`;
      });

      document.querySelectorAll('.filepond--image-preview-wrapper img').forEach(img => {
        img.style.objectFit = 'cover';
        img.style.width = '100%';
        img.style.height = `${previewHeight}px`;
      });
    });
  });

  // apply sizing on initial files too
  pond.on('init', () => {
    requestAnimationFrame(() => {
      document.querySelectorAll('.filepond--item').forEach(it => {
        it.style.maxWidth = `${thumbWidth}px`;
      });
      document.querySelectorAll('.filepond--image-preview-wrapper img').forEach(img => {
        img.style.objectFit = 'cover';
      });
    });
  });

  return pond;
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[data-filepond="true"]').forEach(initFilePondOnInput);
});
