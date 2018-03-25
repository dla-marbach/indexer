#!/bin/bash

echo curl "http://ba14ns21401.adm.ds.fhnw.ch:8983/solr/indexer/update?commit=true" -H "Content-Type: text/xml" --data-binary "<delete><query>session.id:$1</query></delete>"

read -p "Press any key to continue... " -n1 -s

curl "http://ba14ns21401.adm.ds.fhnw.ch:8983/solr/indexer/update?commit=true" -H "Content-Type: text/xml" --data-binary "<delete><query>session.id:$1</query></delete>"
curl "http://ba14ns21401.adm.ds.fhnw.ch:8983/solr/indexer/update?commit=true" -H "Content-Type: text/xml" --data-binary "<commit/>"
