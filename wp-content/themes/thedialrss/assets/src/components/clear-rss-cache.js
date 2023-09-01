// a class which clears the cache of the browser when the button with id "clear-cache" is clicked
class ClearCache {
    constructor () {
        this.cacheClearButton = document.querySelector('#wp-admin-bar-clear-rss-cache a')
        this.events()
    }

    events () {
        this.cacheClearButton.addEventListener('click', () => this.clearCache())
    }

    clearCache () {
        console.log('Clearing cache...')
        // send a vanilla ajax request to WordPress to clear the cache
        const xhr = new XMLHttpRequest()
        xhr.open('GET', '/wp-admin/admin-ajax.php?action=clear_cache', true)
        xhr.send()
        // reload the page
        window.location.reload(true)
    }
}

(() => new ClearCache())()
