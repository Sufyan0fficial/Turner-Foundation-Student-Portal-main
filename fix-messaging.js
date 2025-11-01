// Fixed admin message form handler
document.getElementById('adminMessageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = document.getElementById('adminMessage').value;
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'tfsp_send_admin_message',
            message: message,
            nonce: tfsp_nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('adminMessage').value = '';
            loadAdminMessages(); // Refresh messages
        } else {
            alert('Error sending message: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('Error sending message: Network error');
    });
});

// Fixed loadAdminMessages function
function loadAdminMessages() {
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'tfsp_get_admin_messages',
            nonce: tfsp_nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAdminMessages(data.data);
        } else {
            console.error('Error loading messages:', data.data);
            displayAdminMessages([]); // Show empty state
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        displayAdminMessages([]); // Show empty state
    });
}
