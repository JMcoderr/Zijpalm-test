// This JavaScript file is part of the frontend logic and has a short comment so it is easier to follow.
import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import InlineCode from '@editorjs/inline-code';
import Table from '@editorjs/table'
import Underline from '@editorjs/underline';
import Marker from '@editorjs/marker';

function escapeHtml(value) {
  // Basic escaping so pasted text cannot break the HTML we build below.
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function attachPasteLineBreakHandler(holderElement) {
  // Only attach the handler once, otherwise the same paste event would run multiple times.
  if (holderElement.dataset.editorPasteHandlerAttached === 'true') {
    return;
  }

  // Keep line breaks when someone pastes multiple lines into the editor.
  holderElement.addEventListener('paste', (event) => {
    const target = event.target;
    const editableTarget = target instanceof HTMLElement ? target.closest('[contenteditable="true"]') : null;

    // Ignore paste events outside the editor area.
    if (!editableTarget) {
      return;
    }

    // Read plain text so we can rebuild it with line breaks ourselves.
    const pastedText = event.clipboardData?.getData('text/plain') ?? '';

    // Keep default behavior for single-line text and only normalize multiline paste.
    if (!pastedText.includes('\n') && !pastedText.includes('\r')) {
      return;
    }

    event.preventDefault();

    const normalized = pastedText.replace(/\r\n?/g, '\n');
    // Convert every line into HTML and keep empty lines as <br>.
    const pastedHtml = normalized
      .split('\n')
      .map((line) => (line === '' ? '<br>' : escapeHtml(line)))
      .join('<br>');

    // Use the browser's insertHTML support when it exists.
    if (document.queryCommandSupported?.('insertHTML')) {
      document.execCommand('insertHTML', false, pastedHtml);
      return;
    }

    // Fall back to plain text if insertHTML is not available.
    document.execCommand('insertText', false, normalized);
  });

  holderElement.dataset.editorPasteHandlerAttached = 'true';
}

window.initializeEditorJsHolders = function () {
  // Find every editor holder on the page and initialize it once.
  const editorHolders = document.querySelectorAll('[data-editor-holder][data-editor-input]');

  editorHolders.forEach((holderElement) => {
    // Skip holders that were already initialized.
    if (holderElement.dataset.editorInitialized === 'true') {
      return;
    }

    // Skip hidden holders so we do not initialize the editor too early.
    if (holderElement.offsetParent === null) {
      return;
    }

    // Link the visible editor with the hidden input that stores the JSON data.
    const holderId = holderElement.dataset.editorHolder;
    const inputId = holderElement.dataset.editorInput;
    const editorInput = document.getElementById(inputId);

    // If something is missing, stop instead of crashing the page.
    if (!holderId || !editorInput) {
      return;
    }

    // Load the initial editor data from the global registry if it exists.
    const initialData = window.editorDataRegistry?.[holderId] ?? {};

    // Create the actual EditorJS instance.
    const editor = new EditorJS({
      holder: holderId,
      minHeight: 0,
      minWidth: 0,
      placeholder: 'Voeg hier uw tekst toe',
      data: initialData,
      onReady: function () {
        // Add the paste handler after the editor is ready.
        attachPasteLineBreakHandler(holderElement);
      },
      inlineToolbar: ['bold', 'italic', 'underline', 'marker', 'link'],
      tools: {
        paragraph: {
          config: {
            preserveBlank: true,
          },
        },
        header: Header,
        list: {
          class: EditorjsList,
          inlineToolbar: true,
          config: {
            defaultStyle: 'unordered'
          },
        },
        inlineCode: {
          class: InlineCode,
        },
        table: {
          class: Table,
          config: {
            rows: 2,
            cols: 2,
          },
        },
        underline: Underline,
        marker: Marker,
      },
      onChange: function () {
        // Save the editor content into the hidden input every time it changes.
        editor.save().then((outputData) => {
          editorInput.value = JSON.stringify(outputData);
        }).catch((error) => {
          console.error('Saving failed:', error);
        });
      },
    });

    // Mark the holder as initialized so we do not run this twice.
    holderElement.dataset.editorInitialized = 'true';
    holderElement.editorInstance = editor;
  });
};

window.initializeEditorJsHolders();
