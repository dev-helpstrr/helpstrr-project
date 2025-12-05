<!DOCTYPE html>
<html>

<head>
    <title>Browser Notification Demo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <h1>Welcome to Notification Page</h1>
    <p>You will receive browser notifications here.</p>

    <button id="subscribeBtn">Enable Notifications</button>

   <script>

    if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
  .then(reg => console.log('SW registered', reg))
  .catch(err => console.error('SW registration failed:', err));
}

    // Convert base64 VAPID key to Uint8Array
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
    }

    async function subscribeUser() {
        try {
            // ✅ 1. Register service worker
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registered:', registration);

            // ✅ 2. Request permission
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                alert('Permission denied for notifications.');
                return;
            }

            // ✅ 3. Subscribe user
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array("{{ config('webpush.vapid.public_key') }}")
            });

           // console.log(subscription);

            // ✅ 4. Format subscription for backend
            const subData = {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: subscription.toJSON().keys.p256dh,
                    auth: subscription.toJSON().keys.auth
                },
                contentEncoding: 'aesgcm'
            };

            console.log("Sending subscription to backend:", subData);

            // ✅ 5. Send to Laravel API
            const response = await fetch('/api/v1/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(subData)
            });

            const result = await response.json();
            console.log(result);

            if (response.ok) {
                alert('✅ Subscribed to notifications!');
            } else {
                alert('❌ Subscription failed: ' + result.error);
            }

        } catch (error) {
            console.error('Subscription error:', error);
            alert('Error: ' + error.message);
        }
    }

    document.getElementById('subscribeBtn').addEventListener('click', subscribeUser);
</script>


</body>

</html>
