DROP DATABASE Reactor;
CREATE DATABASE IF NOT EXISTS Reactor;
Use Reactor;

CREATE TABLE Users (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	Name		VARCHAR(4000),
	Email		VARCHAR(4000),
	Password	VARCHAR(1000),
	Created		DATETIME DEFAULT NOW(),
	Active		INT NOT NULL DEFAULT 1
);
CREATE TABLE Channels (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	Name		VARCHAR(4000),
	UserID		INT,
	Slug		VARCHAR(255),
	BroadcasterID	VARCHAR(255),
	Created		DATETIME DEFAULT NOW()
);
CREATE TABLE Subscriptions (
	ID			INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ChannelID		INT,
	SubscriptionTypeID	INT,
	Created			DATETIME DEFAULT NOW(),
	RewardID		VARCHAR(1000)
);
CREATE TABLE Subscription_Types (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	Name		VARCHAR(4000),
	`Field`		VARCHAR(255),
	Description	TEXT,
	TemplateHelp	TEXT
);
INSERT INTO `Subscription_Types` (`ID`, `Name`, `Field`, `Description`, `TemplateHelp`) VALUES
(1, 'Follow', 'channel.follow', NULL, '#user_id#		Unique ID of the user\r\n#user_name#		Name of the user\r\n'),
(2, 'Subscribe', 'channel.subscribe', NULL, '#user_id#		Unique ID of the user\r\n#user_name#		Name of the user\r\n#is_gift#		\"true\" if the sub was gifted'),
(3, 'Bits', 'channel.cheer', NULL, '#user_id#		Unique ID of the user\r\n#user_name#		Name of the user\r\n#is_anonymous#		Is this an anonymous cheer\r\n#message#		The user\'s cheer message\r\n#bits#			The number of bits the user cheered'),
(4, 'Channel Points', 'channel.channel_points_custom_reward_redemption.add', NULL, '#user_id#		Unique ID of the user\r\n#user_name#		Name of the user\r\n#user_input#		The message the user added to the reward\r\n#reward_title#		The name of the reward redeemed\r\n#reward_cost#		The cost of the reward redeemed\r\n#reward_prompt#		The description of the reward redeemed\r\n#redeemed_at		The timestame the reward was redeemed'),
(5, 'Hype Train Start', 'channel.hype_train.begin', NULL, ''),
(6, 'Hype Train Progress', 'channel.hype_train.progress', NULL, ''),
(7, 'Hype Train End', 'channel.hype_train.end', NULL, ''),
(8, 'Stream Start', 'stream.online', NULL, ''),
(9, 'Stream End', 'stream.offline', NULL, '');

CREATE TABLE Callbacks (
	Slug		VARCHAR(100) NOT NULL PRIMARY KEY,
	TwitchID	VARCHAR(65),
	SubscriptionID	INT NOT NULL,
	Secret		VARCHAR(255),
	Created		DATETIME DEFAULT NOW()
);
CREATE TABLE Action_Services (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	UserID		INT NOT NULL,
	ServiceTypeID	INT NOT NULL,
	Name		VARCHAR(4000),
	`Host`		VARCHAR(1000),
	Port		INT,
	`Path`		VARCHAR(4000),
	Username	VARCHAR(1000),
	`Password`	VARCHAR(1000)	
);
CREATE TABLE Actions (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ActionServiceID	INT NOT NULL,
	SubscriptionID	INT NOT NULL,
	`Field`		VARCHAR(4000),
	ValueTemplate	VARCHAR(4000)
);
CREATE TABLE Action_Service_Types (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	Name		VARCHAR(4000),
	FieldName	VARCHAR(255),
	Handler		VARCHAR(255),
	Public		INT NOT NULL DEFAULT 1
);
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('IRC', 'IRC.php', 1, 'Channel');
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('HTTP', 'HTTP.php', 1, 'Field');
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('HTTPS', 'HTTPS.php', 1, 'Field');
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('MQTT', 'MQTT.php', 0, 'Topic');
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('ZeroMQ', 'ZeroMQ.php', 0, 'Key');
INSERT INTO Action_Service_Types (Name, Handler, Public, FieldName) VALUES ('Websocket', 'Websocket.php', 0, '');

CREATE TABLE Twitch_Tokens (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ClientID	VARCHAR(255),
	AccessToken	VARCHAR(255),
	Expires		DATETIME,
	Scope		VARCHAR(4000)
);

CREATE TABLE Twitch_User_Tokens (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ChannelID	INT NOT NULL,
	ClientID	VARCHAR(255),
	AccessToken	VARCHAR(255),
	Expires		DATETIME,
	Scope		VARCHAR(4000)
);
