- hosts: wordpress
  become: yes

  tasks:
    - name: Update package repository
      apt:
        update_cache: yes

    - name: Install Apache
      apt:
        name: apache2
        state: present

    - name: Install MySQL and PHP
      apt:
        name:
          - mysql-server
          - php
          - php-mysql
        state: present

    - name: Install Python MySQL dependencies
      apt:
        name: python3-pymysql
        state: present

    - name: Create MySQL admin user
      mysql_user:
        name: gatha
        password: 12345
        priv: "*.*:ALL,GRANT"
        host: "%"
        state: present
        login_unix_socket: /var/run/mysqld/mysqld.sock

    - name: Create WordPress database
      mysql_db:
        name: wordpress_db_gatha
        state: present
        login_user: gatha
        login_password: 12345

    - name: Download WordPress
      get_url:
        url: https://wordpress.org/latest.tar.gz
        dest: /tmp/wordpress.tar.gz

    - name: Extract WordPress
      unarchive:
        src: /tmp/wordpress.tar.gz
        dest: /var/www/
        remote_src: yes

    - name: Copy wp-config-sample.php to wp-config.php
      command: cp /var/www/wordpress/wp-config-sample.php /var/www/wordpress/wp-config.php
      args:
        creates: /var/www/wordpress/wp-config.php

    - name: Configure wp-config.php
      lineinfile:
        path: /var/www/wordpress/wp-config.php
        regexp: "{{ item.regexp }}"
        line: "{{ item.line }}"
      loop:
        - { regexp: "DB_NAME", line: "define('DB_NAME', 'wordpress_db_gatha');" }
        - { regexp: "DB_USER", line: "define('DB_USER', 'gatha');" }
        - { regexp: "DB_PASSWORD", line: "define('DB_PASSWORD', '12345');" }
        - { regexp: "DB_HOST", line: "define('DB_HOST', 'localhost');" }

    - name: Update Apache VirtualHost for WordPress
      replace:
        path: /etc/apache2/sites-available/000-default.conf
        regexp: 'DocumentRoot /var/www/html'
        replace: 'DocumentRoot /var/www/wordpress'

    - name: Enable the new site configuration
      command: a2ensite 000-default.conf

    - name: Restart Apache to apply changes
      service:
        name: apache2
        state: restarted

- hosts: blog
  become: yes

  tasks:
    - name: Update package repository
      apt:
        update_cache: yes
    - name: Install Apache, MariaDB, and PHP
      apt:
        name:
          - apache2
          - mariadb-server
          - php
          - php-mysqli
          - php-mbstring
        state: present
    - name: Install Python MySQL dependencies
      apt:
        name: python3-pymysql
        state: present
    - name: Create MySQL admin user
      mysql_user:
        name: cyber
        password: pass2023
        priv: "*.*:ALL,GRANT"
        host: "%"
        state: present
        login_unix_socket: /var/run/mysqld/mysqld.sock
    - name: Create Blog database
      mysql_db:
        name: cyber23
        state: present
        login_user: cyber
        login_password: pass2023
    - name: Clone blog repository
      git:
        repo: https://github.com/jonisetiyawan48/ukk_tkj_2.git
        dest: /var/www/ukk_tkj_2
    - name: Import SQL file into database
      mysql_db:
        name: cyber23
        state: import
        target: /var/www/ukk_tkj_2/db.sql
        login_user: cyber
        login_password: pass2023
    - name: Update Apache VirtualHost for blog
      replace:
        path: /etc/apache2/sites-available/000-default.conf
        regexp: 'DocumentRoot /var/www/html'
        replace: 'DocumentRoot /var/www/cyber23'
    - name: Enable the new site configuration
      command: a2ensite 000-default.conf
    - name: Restart Apache to apply changes
      service:
        name: apache2
        state: restarted
