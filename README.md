# StreamerReactor

A simple integration tool to make the twitch.tv EventSub API much more approachable for streamers and enthusiasts.

As twitch.tv requires EventSub applications to provide public HTTPS access on port 443, many users struggle with using the API from home.
StreamerReactor has been designed as a cloud appliance, with no such requirements, and is <strong>freely available to use at https://streamerreactor.viper-7.com/</strong>

[![IMAGE ALT TEXT HERE](https://img.youtube.com/vi/kLpcATTftNA/0.jpg)](https://www.youtube.com/watch?v=kLpcATTftNA)

The list of supported events will expand as twitch provides more through the EventSub API. Right now it can listen for:

* Follows
* Subscriptions (inc Gifts)
* Bits
* Channel Points (with or without messages)
* Hype Trains
* Stream Start/End

Once StreamerReactor receives an event, it can trigger any number of actions, each can format the data a different way and relay it to:

* IRC Chatrooms
* Twitch Chat
* HTTP/S requests
* Our hosted MQTT Server
* Remote MQTT Servers (TODO)
* ZeroMQ Sockets (TODO)
* Remote Websocket (TODO)

With more endpoints planned for the future!

Note that to read information on Subscriptions, Bits, Channel Points and Hype Trains, StreamerReactor will require access to your twitch account. The system requests only the minimum required permission to read this live information, and cannot access any details whatsoever about your user account or the channel itself.

---

# Self Hosting Instructions

The system uses native PHP and MySQL with no dependencies aside from the PDO_MySQL driver (available on all systems with MySQL). You can use any webserver you like, all requests should be rewritten to index.php. Please remember that the system *must* be running publically on port 443 for Twitch to communicate with you.
A Reactor.sql file is included for setting up the database. config.php will need the database name, username, password, along with a Client ID and Secret for a Twitch App, which you can generate at https://dev.twitch.tv/console/apps

---

# Todo

* UI Design
* Logo
* Field Hints
* Email Validation
* Usage Stats?

