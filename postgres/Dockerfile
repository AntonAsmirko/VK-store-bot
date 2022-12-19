FROM postgres:15.1

ENV POSTGRES_PASSWORD "anton"
ENV POSTGRES_USER "anton1"

COPY db /db
COPY filldb.sh filldb.sh 

RUN chmod 777 filldb.sh

EXPOSE 5432
