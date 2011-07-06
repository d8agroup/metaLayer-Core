/*
DROP TABLE IF EXISTS {api_key}_Channels;
DROP TABLE IF EXISTS {api_key}_Sources;
DROP TABLE IF EXISTS {api_key}_Content;
DROP TABLE IF EXISTS {api_key}_Tags;
DROP TABLE IF EXISTS {api_key}_Content_Tags;
*/
-- *****************************************************************************
-- Tables 
-- *****************************************************************************

-- create the apikesy table
CREATE TABLE IF NOT EXISTS ApiKeys (
    apiKey VARCHAR ( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    appTemplate VARCHAR ( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Create the Channel table
CREATE TABLE IF NOT EXISTS {api_key}_Channels (
    id VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    type VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    subType VARCHAR( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    active BIT( 1 ) NOT NULL ,
    inProcess BIT( 1 ) NOT NULL ,
    nextRun INT NOT NULL ,
    json TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    PRIMARY KEY ( id )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Create the Sources Table
CREATE TABLE IF NOT EXISTS {api_key}_Sources (
    id VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    channelId VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    date INT NOT NULL ,
    score INT NULL ,
    name VARCHAR( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    type  VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    subType VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    json TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    PRIMARY KEY ( id )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Create the Content Table
CREATE TABLE IF NOT EXISTS {api_key}_Content (
    id VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    sourceId VARCHAR( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    state VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    date INT NOT NULL ,
    json LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    PRIMARY KEY ( id )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Create the Tags Table
CREATE TABLE IF NOT EXISTS {api_key}_Tags (
    id VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    type VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    text VARCHAR ( 256 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    PRIMARY KEY ( id )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Create the Cotent_Tags
CREATE TABLE IF NOT EXISTS {api_key}_Content_Tags (
    contentId VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    tagId VARCHAR ( 72 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
    PRIMARY KEY ( contentId, tagId )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- *****************************************************************************
-- APIKEY Related Stored Procedures
-- *****************************************************************************
DROP PROCEDURE IF EXISTS ApiKeyExists;
CREATE PROCEDURE ApiKeyExists ( IN apiKeyIn VARCHAR ( 256 ) )
    BEGIN
	SELECT count(apiKey) as 'keyExists' FROM ApiKeys WHERE apiKey = apiKeyIn;
    END;

DROP PROCEDURE IF EXISTS AddApiKey;
CREATE PROCEDURE AddApiKey ( IN apiKeyIn VARCHAR ( 256 ) )
    BEGIN
        INSERT INTO ApiKeys VALUES ( apiKeyIn, NULL );
    END;

DROP PROCEDURE IF EXISTS AddApiKeyWithAppTemplate;
CREATE PROCEDURE AddApiKeyWithAppTemplate ( IN apiKeyIn VARCHAR ( 256 ), IN appTemplateIn VARCHAR( 256 ) )
    BEGIN
        INSERT INTO ApiKeys VALUES ( apiKeyIn, appTemplateIn );
    END;

-- *****************************************************************************
-- Channel Related Stored Procedures
-- *****************************************************************************

-- Create the GetChannelByChannelId stored procedure
DROP PROCEDURE IF EXISTS {api_key}_GetChannelByChannelIds;
CREATE PROCEDURE {api_key}_GetChannelByChannelIds (IN channelIdsAsInArray VARCHAR( 72 ))
    BEGIN
        DECLARE text VARCHAR (256);
        SET text = CONCAT('SELECT json, active, inProcess FROM {api_key}_Channels WHERE id in ', channelIdsAsInArray);
        SET @queryText = text;
        PREPARE query FROM @queryText;
        EXECUTE query;
    END;

-- Create the SaveChannel stored procedure
DROP PROCEDURE IF EXISTS {api_key}_SaveChannel;
CREATE PROCEDURE {api_key}_SaveChannel (
        IN channelId VARCHAR( 72 ),
        IN channelType VARCHAR( 72 ),
        IN channelSubType VARCHAR( 256 ),
        IN channelActive BIT( 1 ),
        IN channelInProcess BIT( 1 ),
        IN channelNextRun INT,
        IN channelJson TEXT)
    BEGIN
        DECLARE count INT DEFAULT 0;
        SET count = (SELECT count(id) FROM {api_key}_Channels WHERE id = channelId);
        IF (count > 0) THEN
            UPDATE
                {api_key}_Channels
            SET
                type = channelType,
                subType = channelSubType,
                active = channelActive,
                inProcess = channelInProcess,
                nextRun = channelNextRun,
                json = channelJson
            WHERE
                id = channelId;
        ELSE
            INSERT
                INTO {api_key}_Channels
            VALUES (
                channelId,
                channelType,
                channelSubType,
                channelActive,
                channelInProcess,
                channelNextRun,
                channelJson);
        END IF;
    END;

-- Create the DeleteChannel stored procedure
DROP PROCEDURE IF EXISTS {api_key}_DeleteChannel;
CREATE PROCEDURE {api_key}_DeleteChannel (IN channelId VARCHAR ( 72 ))
    BEGIN
        DELETE FROM {api_key}_Channels WHERE id = channelId;
    END;

-- Create the SelectNextDueChannel stored procedure
DROP PROCEDURE IF EXISTS {api_key}_SelectNextDueChannel;
CREATE PROCEDURE {api_key}_SelectNextDueChannel (IN dueBeforeTime INT)
    BEGIN
        SELECT
            json
        FROM
            {api_key}_Channels
        WHERE
            nextRun <= dueBeforeTime
        AND
            active = 1
        AND
            inProcess = 0
        ORDER BY
            nextRun ASC
        LIMIT
            1;
    END;

-- Create the ListAllChannels Procedure
DROP PROCEDURE IF EXISTS {api_key}_ListAllChannels;
CREATE PROCEDURE {api_key}_ListAllChannels ()
    BEGIN
        SELECT
            id, type, subType, active, inProcess, nextRun, json
        FROM
            {api_key}_Channels;
    END;


-- *****************************************************************************
-- Content Related Stored Procedures
-- *****************************************************************************

-- Create the SaveContent stored procedure
DROP PROCEDURE IF EXISTS {api_key}_SaveContent;
CREATE PROCEDURE {api_key}_SaveContent (
        contentId VARCHAR ( 72 ),
        contentSourceId VARCHAR ( 72 ),
        contentState VARCHAR ( 72 ),
        contentDate INT,
        contentJson TEXT)
    BEGIN
        DECLARE count INT DEFAULT 0;
        SET count = (SELECT count(id) FROM {api_key}_Content WHERE id = contentId);
        IF (count > 0) THEN
            UPDATE
                {api_key}_Content
            SET
                sourceId = contentSourceId,
                state = contentState,
                date = contentDate,
                json = contentJson
            WHERE
                id = contentId;
        ELSE
            INSERT
                INTO {api_key}_Content
            VALUES (
                contentId,
                contentSourceId,
                contentState,
                contentDate,
                contentJson);
        END IF;
    END;

-- Create the GetContent stored procedure
DROP PROCEDURE IF EXISTS {api_key}_GetContent;
CREATE PROCEDURE {api_key}_GetContent (contentIdsAsInArray VARCHAR (2560))
    BEGIN
        SET @queryText = CONCAT('SELECT c.json as contentjson, s.json as sourcejson FROM {api_key}_Content c JOIN {api_key}_Sources s ON c.sourceId = s.id WHERE c.id in ', contentIdsAsInArray);
        SET @queryText = CONCAT(@queryText, ' order by c.date desc');
        PREPARE query FROM @queryText;
        EXECUTE query;
    END;

-- Create the DeleteContent stored procedure
DROP PROCEDURE IF EXISTS {api_key}_DeleteContent;
CREATE PROCEDURE {api_key}_DeleteContent (IN contentIdToDelete VARCHAR ( 72 ))
    BEGIN
        DELETE FROM {api_key}_Content_Tags WHERE contentId = contentIdToDelete;
        DELETE FROM {api_key}_Content WHERE id = contentIdToDelete;
    END;

-- *****************************************************************************
-- Source Related Stored Procedures
-- *****************************************************************************

-- Create the SaveSource Stored procedure
DROP PROCEDURE IF EXISTS {api_key}_SaveSource;
CREATE PROCEDURE {api_key}_SaveSource (
        IN sourceId VARCHAR ( 72 ),
        IN sourceChannelId VARCHAR ( 72 ),
        IN sourceDate INT,
        IN sourceScore INT,
        IN sourceName VARCHAR ( 256 ),
        IN sourceType VARCHAR ( 72 ),
        IN sourceSubType VARCHAR ( 72 ),
        IN sourceJson TEXT)
    BEGIN
        DECLARE count INT DEFAULT 0;
        SET count = (SELECT count(id) FROM {api_key}_Sources WHERE id = sourceId);
        IF (count > 0) THEN
            UPDATE
                {api_key}_Sources
            SET
                channelId = sourceChannelId,
                date = sourceDate,
                score = sourceScore,
                name = sourceName,
                type = sourceType,
                subType = sourceSubType,
                json = sourceJson
            WHERE
                id = sourceId;
        ELSE
            INSERT
                INTO {api_key}_Sources
            VALUES (
                sourceId,
                sourceChannelId,
                sourceDate,
                sourceScore,
                sourceName,
                sourceType,
                sourceSubType,
                sourceJson);
        END IF;
    END;

-- *****************************************************************************
-- Tag Related Stored Procedures
-- *****************************************************************************

-- Create the AddTag stored procedure
DROP PROCEDURE IF EXISTS {api_key}_AddTag;
CREATE PROCEDURE {api_key}_AddTag (
        IN tagContentId VARCHAR ( 72 ),
        IN tagTagId VARCHAR ( 72 ),
        IN tagTagType VARCHAR ( 72 ),
        IN tagTagText VARCHAR ( 256 ))
    BEGIN
        DECLARE count INT DEFAULT 0;
        SET count = (SELECT COUNT(*) FROM {api_key}_Tags WHERE id = tagTagId);
        IF ( count < 1 ) THEN
            INSERT
                INTO {api_key}_Tags
            VALUES (
                tagTagId,
                tagTagType,
                tagTagText);
        END IF;
        SET count = (SELECT COUNT(*) FROM {api_key}_Content_Tags WHERE contentId = tagContentId AND tagId = tagTagId);
        IF ( count < 1 ) THEN
            INSERT
                INTO {api_key}_Content_Tags
            VALUES (
                tagContentId,
                tagTagId);
        END IF;
    END;

-- Create GetTags Stored Procedure
DROP PROCEDURE IF EXISTS {api_key}_SelectTags;
CREATE PROCEDURE {api_key}_SelectTags ( IN contentTagId VARCHAR ( 72 ) )
    BEGIN
        SELECT
            t.type, t.text
        FROM
            {api_key}_Tags t JOIN {api_key}_Content_Tags ct
                ON t.id = ct.tagId
        WHERE
            ct.contentId = contentTagId;
    END;

-- Create the Remove All Tags Procedure
DROP PROCEDURE IF EXISTS {api_key}_RemoveAllTags;
CREATE PROCEDURE {api_key}_RemoveAllTags ( IN contentTagId VARCHAR ( 72 ) )
    BEGIN
        DELETE FROM
            {api_key}_Content_Tags
        WHERE
            contentId = contentTagId;
    END;

-- Create the Select Source stored procedure
DROP PROCEDURE IF EXISTS {api_key}_GetSource;
CREATE PROCEDURE {api_key}_GetSource ( IN sourceId VARCHAR ( 72 ) )
    BEGIN
        SELECT
            json
        FROM
            {api_key}_Sources
        WHERE
            id = sourceId;
    END;

-- Create the Select All Source stored procedure
DROP PROCEDURE IF EXISTS {api_key}_SelectAllSources;
CREATE PROCEDURE {api_key}_SelectAllSources ()
    BEGIN
        SELECT
            json
        FROM
            {api_key}_Sources;
    END;
