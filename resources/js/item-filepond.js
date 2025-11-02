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
  
  // Check if this is a letter upload (support_letter) or photo upload
  const isLetterUpload = input.id === 'support_letter' || input.name === 'support_letter';
  const acceptAttr = input.getAttribute('accept') || '';
  const acceptsPdf = acceptAttr.includes('pdf') || acceptAttr.includes('.pdf');

  const pond = FilePond.create(input, {
    allowMultiple: false,
    instantUpload: false,
    allowReorder: false,
    credits: false,
    allowImagePreview: !acceptsPdf || isLetterUpload, // Enable image preview for images or letter uploads
    
    // Image preview sizing (height controls preview panel height)
    imagePreviewHeight: previewHeight,

    // Resize settings (keeps client preview reasonable and helps when sending)
    imageResizeTargetWidth: 1200,
    imageResizeTargetHeight: 800,
    imageResizeMode: 'cover',

    // Modern interactive placeholder text (customize based on upload type)
    labelIdle: isLetterUpload
      ? `<div style="text-align:left;padding:8px 12px">
          <div style="font-weight:600;color:#111827;font-size:14px">Click to upload Signed Letter</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px">or
          <span style="font-weight:600">drag and drop</span> JPG/PNG/WEBP/PDF, up to 5MB</div>
        </div>`
      : `<div style="text-align:left;padding:8px 12px">
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
    
    // Max file size (5MB for letters, 2MB for photos)
    maxFileSize: isLetterUpload ? '5MB' : '2MB',
  });

  // Store pond instance on the input element for easy access
  if (input) {
    input.filepond = pond;
  }

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
