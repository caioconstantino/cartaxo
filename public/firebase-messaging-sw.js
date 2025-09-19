importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');
let config = {
        apiKey: "AIzaSyDvvrGgb1k8eJmj5XNjlQnWoruQfcgqbLE",
        authDomain: "shopking-b8b78.firebaseapp.com",
        projectId: "shopking-b8b78",
        storageBucket: "shopking-b8b78.appspot.com",
        messagingSenderId: "806204285496",
        appId: "1:806204285496:web:7b0c79f6df7ba1bba07ccd",
        measurementId: "G-V2SH66NJ2E",
 };
firebase.initializeApp(config);
const messaging = firebase.messaging();
messaging.onBackgroundMessage((payload) => {
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/images/required/firebase-logo.png'
    };
    self.registration.showNotification(notificationTitle, notificationOptions);
});
