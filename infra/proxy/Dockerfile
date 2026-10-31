FROM httpd:2.4

# A imagem oficial vem com os módulos de proxy desligados; liga-os e inclui nossos VirtualHosts.
# O vhosts.conf é montado como volume (compose.yml) para poder ser alterado sem rebuild.
RUN sed -i \
      -e 's/^#\(LoadModule proxy_module .*\)/\1/' \
      -e 's/^#\(LoadModule proxy_http_module .*\)/\1/' \
      /usr/local/apache2/conf/httpd.conf \
 && echo 'Include conf/extra/vhosts.conf' >> /usr/local/apache2/conf/httpd.conf
