FROM jenkins/jenkins:2.580.2-lts-jdk21

# Cliente Docker + plugin compose: o Jenkins comanda o Docker do host pelo socket montado
COPY --from=docker:29.9.2-cli /usr/local/bin/docker /usr/local/bin/docker
COPY --from=docker:29.9.2-cli /usr/local/libexec/docker/cli-plugins /usr/local/libexec/docker/cli-plugins

COPY plugins.txt /usr/share/jenkins/ref/plugins.txt
RUN jenkins-plugin-cli --plugin-file /usr/share/jenkins/ref/plugins.txt
