#!/bin/bash

psql -h localhost -p 5432 -U $POSTGRES_USER -d anton1 -a -f ./db/init-tables/create_category.sql
psql -h localhost -p 5432 -U $POSTGRES_USER -d anton1 -a -f ./db/init-tables/create_items_table.sql
psql -h localhost -p 5432 -U $POSTGRES_USER -d anton1 -a -f ./db/init-tables/create_category_to_item_table.sql

psql -h localhost -U $POSTGRES_USER -p 5432 -d anton1 -a -f ./db/insert-data/insert_items_data.sql
psql -h localhost -U $POSTGRES_USER -p 5432 -d anton1 -a -f ./db/insert-data/insert_category_to_item_data.sql
psql -h localhost -U $POSTGRES_USER -p 5432 -d anton1 -a -f ./db/insert-data/insert_categories_data.sql
