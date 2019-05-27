FROM nginx:alpine

ENV FASTCGI_TARGET='app:9000'
ENV DOCS_TARGET='http://docs:8080/'

COPY nginx.conf /etc/nginx/default.tmpl

RUN apk add --no-cache curl

HEALTHCHECK --interval=30s --timeout=2s CMD curl -f localhost/nginx_status || exit 1

CMD [ "/bin/sh", "-c", "envsubst '${FASTCGI_TARGET} ${DOCS_TARGET}' < /etc/nginx/default.tmpl > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;' || cat /etc/nginx/conf.d/default.conf" ]

