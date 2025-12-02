self.addEventListener("push", function (event) {

    console.log("Push received", event.data ? event.data.text() : "No data");

    let data = { title: "Default title", body: "Default body", url: "/" };
    if (event.data) {
        data = event.data.json();
    }

    const options = {
        body: data.body,
        icon: "/icon.png",
        data: { url: data.url },
    };

    event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    const url = event.notification.data?.url || "/";
    event.waitUntil(clients.openWindow(url));
});
