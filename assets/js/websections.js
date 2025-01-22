
document.addEventListener('DOMContentLoaded', () => {

    // "use strict"

    class SortableList {

        title = null
        draggedItem = null
        lastDraggedItem = null
        currentHover = null
        dragStartClientY

        constructor(draggableGroup) {
            this.initialize(draggableGroup)
        }

        initialize(draggableGroup) {
            this.draggableGroup = draggableGroup
            this.debug = JSON.parse(this.draggableGroup.dataset.debug || 'false')
            this.url = this.draggableGroup.dataset.url
            this.entity = JSON.parse(this.draggableGroup.dataset.entity)
            this.prototype = JSON.parse(this.draggableGroup.dataset.prototype)
            // if(this.debug) console.debug('Prototype:', this.prototype)
            // if(this.debug) console.debug('Entity:', this.entity)
            if(this.debug) console.debug('- New sortable:', this)
            this.draw()
        }

        async fetchItems() {
            const response = await fetch(this.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then((response) => response.json())
            // .then((json) => {
            //     if(this.debug) console.debug('Data sent successfully', json)
            // })
            .catch((error) => {
                if(this.debug) console.error('Error while sending data', error)
            })
            return response
        }

        drawTitle(insertBefore = false) {
            const title = this.draggableGroup.dataset.title || null
            if(title) {
                /** create title element */
                this.title = document.createElement("div")
                /** add icon */
                var icon = document.createElement('span')
                icon.classList.add('fas', 'fa-arrow-down', 'pe-2')
                this.title.appendChild(icon)
                /** add text */
                var text = document.createTextNode(title)
                this.title.appendChild(text)
                this.title.classList.add('text-primary-emphasis', 'text-center', 'pb-2')
                /** insert elements in DOM */
                if(insertBefore) {
                    this.draggableGroup.parentNode.insertBefore(this.title, this.draggableGroup)
                } else {
                    this.draggableGroup.appendChild(this.title)
                }
            } else {
                this.removeTitle()
            }
        }

        removeTitle() {
            /** remove title */
            if(this.title) {
                this.title.remove()
                this.title = null
            }
        }

        removeChildren(element) {
            // element.replaceChildren()
            let child
            do {
                child = element.lastElementChild
                if(child) {
                    child.removeEventListener('dragstart', (event) => this.handleDragStart(event))
                    child.removeEventListener('dragover', (event) => this.handleDragOver(event))
                    child.removeEventListener('dragend', (event) => this.handleDragEnd(event))
                    child.removeEventListener('drop', (event) => this.handleDrop(event))
                    element.removeChild(child)
                }
            } while (child);
        }

        draw() {
            /** prepare items */
            const items = this.fetchItems()
            items.then((data) => {
                if(this.debug) console.debug('Received items:', data)
                /** undraw old items */
                this.unDraw()
                /** draw title & items */
                // this.drawTitle(true)
                if(data) this.updateDraw(data)
            })
        }

        unDraw() {
            /** remove title */
            this.removeTitle()
            /** remove all children */
            this.removeChildren(this.draggableGroup)
        }

        updateDraw(data) {
            this.drawTitle(true)
            data.items.forEach((item) => {
                if(this.debug) console.debug('Drawing item:', item)
                const container = document.createElement('div')
                const proto = this.prototype
                    .replace(/__item.id__/g, item.id)
                    .replace(/__item.name__/g, item.name)
                    .replace(/__item.euid__/g, item.euid)
                    .replace(/__item.sectiontype__/g, item.sectiontype)
                    .replace(/__entity.id__/g, this.entity.id)
                container.innerHTML = proto
                const new_line = container.firstElementChild
                new_line.setAttribute('draggable', item.sectiontype === 'section')
                // new_line.querySelector('.sortable-item-title').textContent = item.title
                /** add eventListeners */
                new_line.addEventListener('dragstart', (event) => this.handleDragStart(event))
                new_line.addEventListener('dragover', (event) => this.handleDragOver(event))
                new_line.addEventListener('dragend', (event) => this.handleDragEnd(event))
                new_line.addEventListener('drop', (event) => this.handleDrop(event))
                /** append to main draggableGroup */
                this.draggableGroup.appendChild(new_line)
            })
        }

        sendData() {
            // Update the data
            this.dragableItems = this.draggableGroup.querySelectorAll('.sortable-item')
            const data = {
                parentFieldName: this.draggableGroup.dataset.parentFieldName,
                items: Array.from(this.dragableItems).map((item) => item.dataset.euid)
            }
            if(this.debug) console.debug('Sending data', data)
            // Send the data
            /** @see https://youtu.be/o5qsUz2vnzg?si=njnPTGZCaDeOsdUS */
            fetch(this.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then((response) => response.json())
            .then((json) => {
                if(this.debug) console.debug('Data sent successfully', json)
                setTimeout(() => {
                    this.unDraw()
                    this.updateDraw(json)
                }, 1000)
            })
            .catch((error) => {
                if(this.debug) console.error('Error while sending data', error)
            })
        }

        startDragItem(item) {
            this.draggedItem = item
            this.draggedItem.style.opacity = 0.3
        }

        stopDragItem() {
            if(this.draggedItem !== null) {
                this.lastDraggedItem = this.draggedItem
                setTimeout(() => {
                    this.lastDraggedItem.style.opacity = 1
                    this.lastDraggedItem = null
                }, 300)
                this.draggedItem = null
            }
            this.exitCurrentHover(this.currentHover)
        }

        enterCurrentHover(newHover) {
            if(this.debug) console.debug('Entering hover:', 'ClientY' + newHover.clientY + 'px / offsetTop: ' + newHover.offsetTop + 'px / Drag start: ' + this.dragStartClientY + 'px')
            if(this.currentHover !== newHover) {
                if(this.currentHover !== null) {
                    this.exitCurrentHover(this.currentHover)
                }
                if(newHover !== null) {
                    this.currentHover = newHover
                    if(this.currentHover !== this.draggedItem) {
                        this.dragStartClientY > newHover.offsetTop ? this.currentHover.style.borderTopWidth = '5px' : this.currentHover.style.borderBottomWidth = '5px'
                    }
                }
            }
        }

        exitCurrentHover(hover) {
            if(this.currentHover !== null && (this.currentHover === hover || hover === null)) {
                this.currentHover.style.borderBottomWidth = ''
                this.currentHover.style.borderTopWidth = ''
                this.currentHover = null
            }
        }

        // Handlers

        handleDragStart(event) {
            this.dragStartClientY = event.clientY
            // if(this.debug) console.debug('- Drag started (' + this.dragStartClientY + 'px):', this)
            this.startDragItem(event.target)
        }

        handleDragOver(event) {
            event.preventDefault() // Necessary. Allows us to drop.
            const newHover = event.target.classList.contains('sortable-item') ? event.target : event.target.closest('.sortable-item')
            if(newHover.classList.contains('sortable-item')) {
                this.enterCurrentHover(newHover)
            }
        }

        handleDragEnd(event) {
            this.stopDragItem()
        }

        handleDrop(event) {
            if(this.currentHover === this.draggedItem) {
                // if(this.debug) console.debug('- Dropped on itself')
            } else {
                const isUp = this.dragStartClientY > this.currentHover.offsetTop
                // if(this.debug) console.debug('- Droped on (up: ' + (isUp ? 'YES' : 'NO') + '):', this.currentHover)
                if(isUp) {
                    this.currentHover.parentNode.insertBefore(this.draggedItem, this.currentHover.previousSibling)
                } else {
                    this.currentHover.parentNode.insertBefore(this.draggedItem, this.currentHover.nextSibling)
                }
                this.sendData()
            }
            this.stopDragItem()
        }
    }

    const sortableList = []
    /** @type {HTMLUListElement[]} */
    const draggableGroups = document.querySelectorAll('.sortable-group')
    draggableGroups.forEach(function(draggableGroup) {
        // create new SortableList instance
        const newSortable = new SortableList(draggableGroup)
        sortableList.push(newSortable)
    })
    // if(this.debug) console.debug('New sortables:', sortableList)

})