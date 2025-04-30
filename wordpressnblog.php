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

vars:
  mysql_root_password: "root"
  mysql_user: "cyber"
  mysql_user_password: "pass2023"
  db_name: "cyber23"
  git_repo: "https://github.com/jonisetiyawan48/ukk_tkj_2.git"
  web_root: "/var/www/html"
task:
  - name: Install apache2
    apt:
      name: apache2
      state: present
      update_cache: true
  - name: Install mysql server and python dependencies
    apt:
      name:
        - "mysql-server"
        - "python3-pymysql"
      state: present



  - name: Set mySQL password
    command: mysql -u root -e 'ALTER USER "root"@"localhost" IDENTIFIED WITH mysql_native_password BY "{{ mysql_root_password}}";'
    when: mysql_root_password is defined
    ignore_errors: true



  - name Create blog database
    mysql_db:
      name: "{{ db_name }}"
      state: present
      login_user: root
      login_password: "{{ mysql_root_password}}"


  - name: Create mySQL user
    community.mysql.mysql_user:
    name: "{{ mysql_user }}"
    password: "{{ mysql_user_password }}"
    priv: "{{ db_name }}.*:ALL"
    state: present
    host: localhost
    login_user: root
    login_password: "{{ mysql_root_password }}"
  
  - name: Install php and dependencies
    apt:
      name:
        - "php8.3"
        - "php8.3-mysqli"
        - "php8.3-mbstring"
        - "git"
      state: present



  - name: Check if web root directory exists
    stat:
      path: "{{ web_root }}"
    register: web_root_stat


  - name: Ensure web root directory is empty
    command: rm -rf {{ web_root }}
    when: web_root_stat.stat.exists and web_root_stat.stat.isdir


  - name: Clone Git repository if not present
    git:
      repo: "{{ git repo }}"
      dest: "{{ web_root }}/"
      update: yes



  - name: Import SQL file into database
    mysql_db:
      name: "{{ db_name }}"
      state: import
      target: "{{ web_root }}/db.sql"
      login_user: root
      login_password: "{{ mysql_root_password }}"
