import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import InlineCode from '@editorjs/inline-code';
import Table from '@editorjs/table'
import Underline from '@editorjs/underline';
import Marker from '@editorjs/marker';

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
      inlineToolbar: ['bold', 'italic', 'underline', 'marker', 'link'],
      tools: {
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
