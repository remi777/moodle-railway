FROM moodlehq/moodle-php-apache:8.3-bookworm

# Clone the Moodle 5.2 stable branch (tracks latest patch releases) and remove the .git directory to reduce image size.
# Note: Moodle 5.x serves from the new public/ subdirectory; the Apache DocumentRoot is set in the entrypoint.
RUN git clone --depth 1 -b MOODLE_502_STABLE https://github.com/moodle/moodle.git /var/www/html \
 && rm -rf /var/www/html/.git \
 && chown -R www-data:www-data /var/www/html

# Add the over30 child theme into the public webroot.
COPY theme/over30 /var/www/html/public/theme/over30
RUN chown -R www-data:www-data /var/www/html/public/theme/over30

# Add the MCP web-service plugin (exposes Moodle web services as MCP tools).
COPY webservice-mcp /var/www/html/public/webservice/mcp
RUN chown -R www-data:www-data /var/www/html/public/webservice/mcp

# Add the over30 tutors plugin (public tutor pages + directory).
COPY local/over30tutors /var/www/html/public/local/over30tutors
RUN chown -R www-data:www-data /var/www/html/public/local/over30tutors

# Pretty URLs for tutor pages (/tutor/<slug>, /tutorzy).
COPY apache-tutors.conf /etc/apache2/conf-enabled/tutors.conf
RUN a2enmod rewrite

# Install and configure the runtime entrypoint
COPY railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/railway-entrypoint.sh"]
