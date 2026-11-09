pipeline {
    agent any

    environment {
        APP_ENV = 'testing'
        DB_CONNECTION = 'sqlite'
        DB_DATABASE = ':memory:'
        CACHE_STORE = 'array'
        SESSION_DRIVER = 'array'
        QUEUE_CONNECTION = 'sync'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'npm ci'
                sh 'npm run build'
            }
        }

        stage('Code Style (Laravel Pint)') {
            steps {
                sh './vendor/bin/pint --test'
            }
        }

        stage('Static Analysis (Larastan Level 6)') {
            steps {
                sh './vendor/bin/phpstan analyse --level=6'
            }
        }

        stage('Automated Tests (Pest)') {
            steps {
                sh 'php artisan test --parallel'
            }
        }
    }

    post {
        always {
            cleanWs()
        }
        failure {
            echo "Pipeline falló en alguna etapa de validación de calidad de SIGR."
        }
    }
}

