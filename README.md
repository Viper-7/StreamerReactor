# StreamerReactor

A simple integration tool to make the twitch.tv EventSub API much more approachable for streamers and enthusiasts.

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
* MQTT Servers
* ZeroMQ Sockets
* Remote Websocket




# Todo

* UI Design
* Logo
* Field Hints
* Email Validation
* Twitch Username to broadcaster_user_id ???
* Usage Stats?

