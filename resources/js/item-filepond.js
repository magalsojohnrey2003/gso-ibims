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
  
  // Support both images and PDFs if accept attribute includes PDF
  const acceptTypes = input.accept || '';
  const supportsPdf = acceptTypes.includes('pdf') || acceptTypes.includes('.pdf');

  const pond = FilePond.create(input, {
    allowMultiple: false,
    instantUpload: false,
    allowReorder: false,
    credits: false,
    allowImagePreview: true,
    allowFileTypeValidation: true,

    // Image preview sizing (height controls preview panel height)
    imagePreviewHeight: previewHeight,

    // Resize settings (keeps client preview reasonable and helps when sending)
    imageResizeTargetWidth: 1200,
    imageResizeTargetHeight: 800,
    imageResizeMode: 'cover',

    // Modern interactive placeholder text (no default image fallback)
    labelIdle: input.getAttribute('data-label-idle') || `
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

  // adjust DOM for thumbnail sizing after FilePond mounts
  pond.on('addfile', (error, file) => {
    // run in next tick to ensure DOM present
    requestAnimationFrame(() => {
      // Only apply image sizing if it's an image file
      const isImage = file && file.file && file.file.type && file.file.type.startsWith('image/');
      
      document.querySelectorAll('.filepond--item').forEach(it => {
        it.style.maxWidth = `${thumbWidth}px`;
        if (isImage) {
          it.style.maxHeight = `${previewHeight + 20}px`;
        }
      });

      if (isImage) {
        document.querySelectorAll('.filepond--image-preview-wrapper img').forEach(img => {
          img.style.objectFit = 'cover';
          img.style.width = '100%';
          img.style.height = `${previewHeight}px`;
        });
      }
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
