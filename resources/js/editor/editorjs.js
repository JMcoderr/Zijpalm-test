import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import InlineCode from '@editorjs/inline-code';
import Table from '@editorjs/table'
import Underline from '@editorjs/underline';
import Marker from '@editorjs/marker';

// Hidden input field to store the editor data
const editorInput = document.getElementById('editorjs-data');

// Save the editor data to the hidden input field
function saveEditorData() {
  editor.save().then((outputData) => {
    editorInput.value = JSON.stringify(outputData);
  }).catch((error) => {
    console.error('Saving failed:', error);
  });
}

const editor = new EditorJS({
  holder: 'editorjs',
  minHeight: 0,
  minWidth: 0,
  placeholder: "Voeg hier uw tekst toe",
  // Set the data property to the old data if it exists
  // This will allow you to edit the existing data
  data: window.editordata ?? {},
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
    marker: Marker
  },
  onChange: function () {
    saveEditorData();
  },
})
