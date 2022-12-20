FROM postgres:15.1

COPY db /db
COPY filldb.sh filldb.sh 

RUN chmod 777 filldb.sh

EXPOSE 5432
