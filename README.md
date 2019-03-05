# flmgr

PHP REST api

Asennettu apache serverille ja käyttää mysql kantaa, eli jos löytyy samat niin pitäisi toimia.

Jos asennat omalle niin luo ensiksi mysql kanta esim. cpanelista. 

Lisää "fleetmanager/config/database.php" tiedostoon tarvittavat tiedot (servername, dbname, username ja pass).
Aja "/fleetmanager/config/init_vehicles.php". Tämä luo relevantin taulun ja täyttää sen "/data/vehicles.csv" tiedoston tiedoilla. 

https://app.getpostman.com/join-team?invite_code=6fab4263e5b489dacae084c2c34e6ab2
Oheisessa postman teamissa on käyttäjä nimeltä "jepapeba" ja sen collectioneissa on "fleetmanager" collection jossa on vaaditut requestit.
Siinä olevat urlit toimivat niin voit testailla valmiiksi hostattua ja alustettua versiota.
