JaCoW Reference Engine
========================

Requirements: PHP 7.2

To improve performance, implement the recommendations from this guide
https://symfony.com/doc/current/performance.html

### How to install:

1. Git clone this project to your desired location
2. Download the required libraries by running ``composer update`` in the root of the project
    1. This will also aid you in generating the ``app/config/parameters.yml``
    2. If you are using a non oracle DB, remove the Oracle Specific service in the services.yml
3. Generate the database by running ``php bin/console doctrine:schema:create``
4. Import all the included data by running ``php bin/console doctrine:fixtures:load``
5. Add the first administrator run:
 ``php bin/console fos:user:create`` This will give you a wizard to create the first user,
Then promote that user to be an administrator  
``php bin/console fos:user:promote username ROLE_ADMIN``
6. (optional) Set up a cron to automatically import unpublished conferences:
    
    ``0 * * * * php /web/path/bin/console app:refresh > /dev/null`` 
7. Ensure that the web server has write permissions to var/ 
8. Add vhost entry to your preferred webserver, (apache examples provided in vhosts/) 
     n.b. Only expose mount the root of web/ folder do not mount the root of the project.


 
Originally developed by: Josh Peters (ANSTO) for IPAC'19 
