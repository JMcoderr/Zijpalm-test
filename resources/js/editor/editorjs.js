import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import InlineCode from '@editorjs/inline-code';
import Table from '@editorjs/table'
import Underline from '@editorjs/underline';
import Marker from '@editorjs/marker';

function escapeHtml(value) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function attachPasteLineBreakHandler(holderElement) {
  if (holderElement.dataset.editorPasteHandlerAttached === 'true') {
    return;
  }

  holderElement.addEventListener('paste', (event) => {
    const target = event.target;
    const editableTarget = target instanceof HTMLElement ? target.closest('[contenteditable="true"]') : null;

    if (!editableTarget) {
      return;
    }

    const pastedText = event.clipboardData?.getData('text/plain') ?? '';

    // Keep default behavior for single-line text and only normalize multiline paste.
    if (!pastedText.includes('\n') && !pastedText.includes('\r')) {
      return;
    }

    event.preventDefault();

    const normalized = pastedText.replace(/\r\n?/g, '\n');
    const pastedHtml = normalized
      .split('\n')
      .map((line) => (line === '' ? '<br>' : escapeHtml(line)))
      .join('<br>');

    if (document.queryCommandSupported?.('insertHTML')) {
      document.execCommand('insertHTML', false, pastedHtml);
      return;
    }

    document.execCommand('insertText', false, normalized);
  });

  holderElement.dataset.editorPasteHandlerAttached = 'true';
}

window.initializeEditorJsHolders = function () {
  const editorHolders = document.querySelectorAll('[data-editor-holder][data-editor-input]');

  editorHolders.forEach((holderElement) => {
    if (holderElement.dataset.editorInitialized === 'true') {
      return;
    }

    if (holderElement.offsetParent === null) {
      return;
    }

    const holderId = holderElement.dataset.editorHolder;
    const inputId = holderElement.dataset.editorInput;
    const editorInput = document.getElementById(inputId);

    if (!holderId || !editorInput) {
      return;
    }

    const initialData = window.editorDataRegistry?.[holderId] ?? {};

    const editor = new EditorJS({
      holder: holderId,
      minHeight: 0,
      minWidth: 0,
      placeholder: 'Voeg hier uw tekst toe',
      data: initialData,
      onReady: function () {
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
        editor.save().then((outputData) => {
          editorInput.value = JSON.stringify(outputData);
        }).catch((error) => {
          console.error('Saving failed:', error);
        });
      },
    });

    holderElement.dataset.editorInitialized = 'true';
    holderElement.editorInstance = editor;
  });
};

window.initializeEditorJsHolders();
