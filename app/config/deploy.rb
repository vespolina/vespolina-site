set :application, "Vespolina"
set :domain,      "try.vespolina.org"
set :user,        "deploy"
set :app_path,    "app"

set :repository,  "https://github.com/vespolina/vespolina-site.git"
set :scm,         :git
# Or: `accurev`, `bzr`, `cvs`, `darcs`, `subversion`, `mercurial`, `perforce`, or `none`

set :model_manager, "doctrine"
# Or: `propel`

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Symfony2 migrations will run

default_run_options[:pty] = true # to enable askpass

set :use_sudo, true # Needed for permissions :(
set :keep_releases,  3

set :writable_dirs,       ["app/cache", "app/logs"]
set :webserver_user,      "www-data"
set :permission_method,   :chown
set :use_set_permissions, true

set :shared_files,    ["app/config/parameters.yml"]
set :shared_children, [app_path + "/logs", web_path + "/uploads", "vendor"]

set :use_composer,   true
set :composer_options, "-n --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress"
set :update_vendors, false
set :assets_install, true

set :stages, %w{production staging}
set :default_stage, "staging"
set :stage_dir, "app/config/deployment"

require 'capistrano/ext/multistage'

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

after "deploy", "deploy:cleanup"

after "deploy:restart" do
    run "#{try_sudo} chown -R #{webserver_user} #{latest_release}/#{app_path}/cache"
    run "#{try_sudo} service apache2 graceful"
end