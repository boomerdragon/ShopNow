FROM php:8.3-cli

WORKDIR /app

# Install required PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Create a directory for session storage if needed
RUN mkdir -p /tmp/sessions && chmod 777 /tmp/sessions

# Expose port 8081 for the admin interface
EXPOSE 8081

# Run PHP built-in web server
CMD ["php", "-S", "0.0.0.0:8081"]
