# StreamerReactor

A simple integration tool to make the twitch.tv EventSub API much more approachable for streamers and enthusiasts.

As twitch.tv requires EventSub applications to provide public HTTPS access on port 443, many users struggle with using the API from home.
StreamerReactor has been designed as a cloud appliance, with no such requirements, and is freely available to use at https://streamerreactor.viper-7.com/


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
* MQTT Servers (TODO)
* ZeroMQ Sockets (TODO)
* Remote Websocket (TODO)

With more endpoints planned for the future!

Note that to read information on Subscriptions, Bits, Channel Points and Hype Trains, StreamerReactor will require access to your twitch account. The system requests only the minimum required permission to read this live information, and cannot access any details whatsoever about your user account or the channel itself.


The main shortcoming right now is the requirement of the broadcaster_user_id value, which is generally considered public information, but Twitch do not appear to offer an API for it any more. I have been getting the ID by visiting the stream on twitch, opening the browser's developer tools, switching to the Network tab, pressing f5 to reload the page, looking for the 'gql' requests to twitch, and clicking through them, looking at the Response tab, until i find one that starts like [{"data":{"user":{"id":"25725272". This 25725272 would be the Broadcaster ID the system asks you when creating a channel.
This is only temporary, if anyone knows a proper solution for this without requiring permission to manage a user's channel, please get in touch :)


# Todo

* UI Design
* Logo
* Field Hints
* Email Validation
* Twitch Username to broadcaster_user_id ???
* Usage Stats?

