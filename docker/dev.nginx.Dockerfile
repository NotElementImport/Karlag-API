FROM nginx:latest
COPY ./docker/dev.nginx.conf /etc/nginx/conf.d/default.conf