## Deployment:
Make sure that you have installed last Docker version (https://www.docker.com/products/docker-desktop/). Clone current repository and execute following steps in the project root directory:
```sh
$ docker-compose build          # Build PHP image.
$ docker-compose up -d          # Run PHP and Mysql containers.
$ docker-compose exec php bash  # Go inside PHP container.
$ php script.php                # Execute PHP script. It fills DB with data. Check docker-compose.yaml for DB creds.
```
