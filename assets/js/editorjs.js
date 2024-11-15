import EditorJS from '@editorjs/editorjs'

// class Editorjs {
//     constructor(field) {
//         this.field = field
//         this.field.addEventListener('input', this.editorjs.bind(this))
//         this.editorjs()
//     }

//     editorjs() {
//         this.field.style.overflow = 'hidden'
//         this.field.style.resize = 'vertical'
//         this.field.style.boxSizing = 'border-box'
//         this.field.style.height = 'auto'

//         // this check is needed because the <textarea> element can be inside a
//         // minimizable panel, causing its scrollHeight value to be 0
//         if (this.field.scrollHeight > 0) {
//             this.field.style.height = this.field.scrollHeight + 'px'
//         }
//     }
// }

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-editorjs-field]').forEach(function (field) {
        field.style.border = '1px solid orange'
        const editor = new EditorJS()
        // new Editorjs(field)
    })
})
