CREATE TABLE IF NOT EXISTS category(
    ID INT PRIMARY KEY NOT NULL,
    CAT_NAME VARCHAR(256) NOT NULL,
    CAT_DESCRIPTION VARCHAR(1024) NOT NULL,
    MEDIA_ID VARCHAR(1024) 
);