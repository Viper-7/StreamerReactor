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
	TwitchID		VARCHAR(65),
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
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Follow', 'channel.follow');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Subscribe', 'channel.subscribe');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Bits', 'channel.cheer');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Channel Points', 'channel.channel_points_custom_reward_redemption.add');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Hype Train Start', 'channel.hype_train.begin');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Hype Train Progress', 'channel.hype_train.progress');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Hype Train End', 'channel.hype_train.end');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Stream Start', 'stream.online');
INSERT INTO Subscription_Types (Name, `Field`) VALUES ('Stream End', 'stream.offline');

CREATE TABLE Callbacks (
	Slug		VARCHAR(100) NOT NULL PRIMARY KEY,
	SubscriptionID	INT NOT NULL,
	Challenge	VARCHAR(255),
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
INSERT INTO Action_Service_Types (Name, Handler, Public) VALUES ('IRC', 'IRC.php', 1);
INSERT INTO Action_Service_Types (Name, Handler, Public) VALUES ('HTTP', 'HTTP.php', 1);
INSERT INTO Action_Service_Types (Name, Handler, Public) VALUES ('MQTT', 'MQTT.php', 0);
INSERT INTO Action_Service_Types (Name, Handler, Public) VALUES ('ZeroMQ', 'ZeroMQ.php', 0);
INSERT INTO Action_Service_Types (Name, Handler, Public) VALUES ('Websocket', 'Websocket.php', 0);

CREATE TABLE Twitch_Tokens (
	ID		INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ClientID	VARCHAR(255),
	AccessToken	VARCHAR(255),
	Expires		DATETIME,
	Scope		VARCHAR(4000)
);

