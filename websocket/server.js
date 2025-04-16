const express = require('express');
const http = require('http');
const https = require('https');
const socketIo = require('socket.io');
const cors = require('cors');
const winston = require('winston');
const amqp = require('amqplib');
const jwt = require('jsonwebtoken');
const fs = require('fs');
const axios = require('axios');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// Configuration - SECURITY-CRITICAL: Make sure to set these env variables in production
const config = {
  jwt: {
    secret: process.env.JWT_SECRET || (process.env.NODE_ENV === 'production' 
      ? (function() { 
          console.error('ERROR: JWT_SECRET environment variable not set in production!'); 
          process.exit(1); 
        })() 
      : 'dev_secret_only_for_development'),
    expiresIn: '24h'
  },
  server: {
    port: process.env.PORT || 3030,
    // Only enforce HTTPS in production if explicitly set
    useHTTPS: process.env.NODE_ENV === 'production' 
      ? (process.env.USE_HTTPS !== 'false') 
      : (process.env.USE_HTTPS === 'true'),
    apiUrl: process.env.API_URL || 'http://127.0.0.1:8000/api',
    trustProxy: process.env.TRUST_PROXY === 'true'
  },
  rabbitmq: {
    host: process.env.RABBITMQ_HOST || 'localhost',
    port: process.env.RABBITMQ_PORT || 5672,
    user: process.env.RABBITMQ_USER || (process.env.NODE_ENV === 'production' 
      ? (function() { 
          console.error('ERROR: RABBITMQ_USER environment variable not set in production!'); 
          process.exit(1); 
        })() 
      : 'guest'),
    pass: process.env.RABBITMQ_PASS || (process.env.NODE_ENV === 'production' 
      ? (function() { 
          console.error('ERROR: RABBITMQ_PASS environment variable not set in production!'); 
          process.exit(1); 
        })() 
      : 'guest'),
    exchange: 'notification_events',
    vhost: process.env.RABBITMQ_VHOST || '/'
  },
  cors: {
    // Restrict allowed origins
    origin: process.env.ALLOWED_ORIGINS 
      ? process.env.ALLOWED_ORIGINS.split(',') 
      : (process.env.NODE_ENV === 'production' 
          ? (function() { 
              console.error('ERROR: ALLOWED_ORIGINS environment variable not set in production!'); 
              process.exit(1); 
            })() 
          : ['http://localhost:3000', 'http://127.0.0.1:3000', 'http://localhost:8081']),
    methods: ["GET", "POST"],
    allowedHeaders: ["Content-Type", "Authorization"],
    credentials: true
  },
  security: {
    rateLimitWindow: parseInt(process.env.RATE_LIMIT_WINDOW, 10) || 60000,  // 1 minute
    rateLimitMax: parseInt(process.env.RATE_LIMIT_MAX, 10) || 100,          // 100 requests per window
    socketRateLimitMax: parseInt(process.env.SOCKET_RATE_LIMIT_MAX, 10) || 10, // 10 connections per minute
    contentSecurityPolicy: process.env.CONTENT_SECURITY_POLICY === 'true',
    accessControlMaxAge: 3600,
    socketAuthTimeoutMs: 5000 // Socket auth timeout (5 seconds)
  }
};

// Setup logging with sensitive data filtering
const sensitiveFields = ['password', 'token', 'secret', 'authorization', 'jwt'];

const filterSensitiveData = winston.format((info) => {
  if (typeof info.message === 'string') {
    let message = info.message;
    sensitiveFields.forEach(field => {
      const regex = new RegExp(`(["']?${field}["']?\\s*[=:]\\s*["']?)([^"'\\s]+)(["']?)`, 'gi');
      message = message.replace(regex, '$1[REDACTED]$3');
    });
    info.message = message;
  }
  return info;
});

const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || (process.env.NODE_ENV === 'production' ? 'info' : 'debug'),
  format: winston.format.combine(
    filterSensitiveData(),
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.printf(({ timestamp, level, message, stack }) => {
      return `${timestamp} ${level}: ${message}${stack ? `\n${stack}` : ''}`;
    })
  ),
  transports: [
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.simple()
      )
    }),
    new winston.transports.File({ 
      filename: 'server.log',
      maxsize: 5242880, // 5MB
      maxFiles: 5,
    })
  ]
});

// Create Express app
const app = express();

// Security middleware
if (config.server.trustProxy) {
  app.set('trust proxy', 1); // Trust first proxy
}

// Apply security headers with Helmet
if (process.env.NODE_ENV === 'production') {
  app.use(helmet({
    contentSecurityPolicy: config.security.contentSecurityPolicy ? undefined : false,
    crossOriginEmbedderPolicy: false, // Needed for Socket.IO
    crossOriginOpenerPolicy: { policy: 'same-origin-allow-popups' },
    crossOriginResourcePolicy: { policy: 'cross-origin' } // Needed for Socket.IO
  }));
}

// Create HTTP or HTTPS server based on configuration
let server;
if (config.server.useHTTPS) {
  try {
    // In production, look for real certificates
    const certPath = process.env.SSL_CERT_PATH || 'ssl/cert.pem';
    const keyPath = process.env.SSL_KEY_PATH || 'ssl/privkey.pem';
    const caPath = process.env.SSL_CA_PATH || 'ssl/chain.pem';
    
    if (!fs.existsSync(certPath) || !fs.existsSync(keyPath)) {
      throw new Error(`SSL certificates not found at ${certPath} or ${keyPath}`);
    }
    
    const privateKey = fs.readFileSync(keyPath, 'utf8');
    const certificate = fs.readFileSync(certPath, 'utf8');
    const ca = fs.existsSync(caPath) ? fs.readFileSync(caPath, 'utf8') : undefined;

    const credentials = {
      key: privateKey,
      cert: certificate,
      ca: ca
    };

    server = https.createServer(credentials, app);
    logger.info('HTTPS server created with SSL certificates');
  } catch (error) {
    if (process.env.NODE_ENV === 'production') {
      logger.error(`Fatal error creating HTTPS server: ${error.message}`);
      process.exit(1); // Exit in production if HTTPS is required but certificates are missing
    } else {
      logger.error(`Error creating HTTPS server: ${error.message}`);
      logger.info('Falling back to HTTP server for development');
      server = http.createServer(app);
    }
  }
} else {
  if (process.env.NODE_ENV === 'production') {
    logger.warn('WARNING: Running in production without HTTPS is not recommended!');
  }
  server = http.createServer(app);
  logger.info('HTTP server created');
}

// Apply rate limiting
const apiLimiter = rateLimit({
  windowMs: config.security.rateLimitWindow,
  max: config.security.rateLimitMax,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Too many requests, please try again later' }
});

// Create Socket.IO server with improved security
const io = socketIo(server, {
  cors: config.cors,
  pingTimeout: 30000,
  pingInterval: 10000,
  transports: ['websocket', 'polling'],
  maxHttpBufferSize: 1e6, // 1MB
  connectTimeout: 45000
});

// Middleware
app.use(express.json({ 
  limit: '1mb',
  verify: (req, res, buf, encoding) => {
    // Basic JSON payload validation
    if (buf.length > 0) {
      try {
        JSON.parse(buf.toString(encoding || 'utf8'));
      } catch (e) {
        logger.warn(`Invalid JSON received: ${e.message}`);
        throw new Error('Invalid JSON');
      }
    }
  }
}));
app.use(cors(config.cors));

// Apply rate limiting to API endpoints
app.use('/api/', apiLimiter);

// Health check endpoint (public)
app.get('/health', (req, res) => {
  res.status(200).json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: process.env.VERSION || '1.0.0'
  });
});

// Emit endpoint with authentication
app.post('/emit', async (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Authentication required' });
  }

  try {
    const token = authHeader.split(' ')[1];
    const decoded = jwt.verify(token, config.jwt.secret);
    
    // Validate the token contains expected claims
    if (!decoded.sub) {
      throw new Error('Invalid token structure');
    }
    
    const { userId, event } = req.body;
    if (!userId || !event || !event.type) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Validate that emitter has permission to emit to this user
    // This is a security measure to prevent unauthorized message sending
    if (decoded.roles && !decoded.roles.includes('ROLE_ADMIN')) {
      if (decoded.sub !== userId) {
        logger.warn(`Unauthorized emit attempt: ${decoded.sub} tried to emit to ${userId}`);
        return res.status(403).json({ error: 'Not authorized to emit to this user' });
      }
    }

    // Validate event structure
    if (typeof event !== 'object' || Array.isArray(event)) {
      return res.status(400).json({ error: 'Invalid event format' });
    }

    logger.info(`Emitting event to user ${userId}: ${event.type}`);
    io.to(userId).emit(event.type, event);
    res.status(200).json({ success: true });
  } catch (error) {
    logger.error(`Error in /emit endpoint: ${error.message}`);
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Invalid or expired token' });
    }
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Active connections tracking for rate limiting and monitoring
const connections = {
  total: 0,
  byUser: {},
  rateLimit: {}, // Store rate limiting info
  messageRateLimit: {} // Track message rates per user
};

// User roles and permissions cache (could be expanded with a DB or Redis)
const userPermissions = {
  // userId: { roles: ['role1', 'role2'], permissions: ['perm1', 'perm2'] }
};

// Validate event data to prevent injection attacks
function validateEventData(event) {
  if (!event || typeof event !== 'object') {
    return false;
  }
  
  // Check required fields
  if (!event.type || typeof event.type !== 'string') {
    return false;
  }
  
  // Validate type doesn't contain injection attempts (basic validation)
  if (event.type.includes('<') || event.type.includes('>') || event.type.includes('/')) {
    return false;
  }
  
  return true;
}

// Authentication middleware for socket connections with timeout
io.use(async (socket, next) => {
  // Create a timeout that will reject the connection if auth takes too long
  const authTimeout = setTimeout(() => {
    logger.warn(`Auth timeout for connection from ${socket.handshake.address}`);
    next(new Error('Authentication timeout'));
  }, config.security.socketAuthTimeoutMs);
  
  try {
    const { userId, token } = socket.handshake.auth;
    
    if (!userId) {
      clearTimeout(authTimeout);
      return next(new Error('Authentication failed: userId required'));
    }

    // Add basic rate limiting based on IP
    const userIP = socket.handshake.address;
    if (!connections.rateLimit[userIP]) {
      connections.rateLimit[userIP] = { count: 0, lastReset: Date.now() };
    }
    
    // Reset rate limiting after window period
    if (Date.now() - connections.rateLimit[userIP].lastReset > config.security.rateLimitWindow) {
      connections.rateLimit[userIP] = { count: 0, lastReset: Date.now() };
    }
    
    // Apply connection rate limit
    connections.rateLimit[userIP].count++;
    if (connections.rateLimit[userIP].count > config.security.socketRateLimitMax) {
      logger.warn(`Rate limit exceeded for IP: ${userIP}`);
      clearTimeout(authTimeout);
      return next(new Error('Too many connection attempts'));
    }

    // If we have a token, verify it with the API
    if (token) {
      try {
        // Option 1: Verify the token locally
        const decoded = jwt.verify(token, config.jwt.secret);
        
        // Option 2: Verify with the API for more complete validation
        const response = await axios.get(`${config.server.apiUrl}/auth/validate-token`, {
          headers: { 'Authorization': `Bearer ${token}` },
          timeout: 3000 // 3 second timeout
        });
        
        if (response.data && response.data.valid && response.data.user && response.data.user.uuid === userId) {
          // Valid token, store user info in socket
          socket.user = response.data.user;
          
          // Store roles and permissions for later authorization checks
          if (response.data.user.roles) {
            userPermissions[userId] = {
              roles: response.data.user.roles,
              lastUpdated: new Date()
            };
          }
          
          logger.info(`User authenticated via JWT: ${userId}`);
        } else {
          logger.warn(`Invalid token for user: ${userId}`);
          clearTimeout(authTimeout);
          return next(new Error('Authentication failed: Invalid token'));
        }
      } catch (error) {
        if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
          logger.warn(`Invalid JWT token: ${error.message}`);
          clearTimeout(authTimeout);
          return next(new Error('Authentication failed: ' + error.message));
        }
        
        // If API verification fails, fall back to local validation only in development
        if (process.env.NODE_ENV !== 'production') {
          logger.warn(`Error validating token with API: ${error.message}. Falling back to local validation in development.`);
          // In development, proceed with caution
        } else {
          logger.error(`Error validating token: ${error.message}`);
          clearTimeout(authTimeout);
          return next(new Error('Authentication failed: Unable to validate token'));
        }
      }
    } else if (process.env.NODE_ENV === 'production') {
      logger.warn(`Production connection without token for userId: ${userId}`);
      clearTimeout(authTimeout);
      return next(new Error('Authentication failed: Token required in production'));
    }

    // Store user metadata in socket
    socket.userId = userId;
    socket.joinTime = new Date();
    socket.metadata = {
      connectionId: socket.id,
      connectedAt: socket.joinTime,
      ip: socket.handshake.address, // Store for audit purposes
      userAgent: socket.handshake.headers['user-agent']
    };

    // Set up message rate limiting for this connection
    if (!connections.messageRateLimit[userId]) {
      connections.messageRateLimit[userId] = {
        count: 0,
        lastReset: Date.now()
      };
    }
    
    clearTimeout(authTimeout);
    return next();
  } catch (error) {
    clearTimeout(authTimeout);
    logger.error(`Socket authentication error: ${error.message}`);
    return next(new Error('Authentication error'));
  }
});

// Socket connection handling
io.on('connection', (socket) => {
  const userId = socket.userId;
  
  logger.info(`User connected: ${userId}, Socket ID: ${socket.id}`);
  
  // Track connections
  connections.total++;
  if (!connections.byUser[userId]) {
    connections.byUser[userId] = [];
  }
  connections.byUser[userId].push(socket.id);
  
  // Join user-specific room
  socket.join(userId);
  logger.info(`User ${userId} joined their room`);
  
  // Emit connection event to user
  socket.emit('connected', { 
    connectionId: socket.id,
    timestamp: new Date().toISOString(),
    status: 'connected'
  });

  // Detect and handle ping-pong for connection liveliness
  let lastPing = Date.now();
  
  socket.on('ping', () => {
    lastPing = Date.now();
    socket.emit('pong', { timestamp: new Date().toISOString() });
  });

  // Cleanup on disconnect
  socket.on('disconnect', (reason) => {
    logger.info(`User disconnected: ${userId}, Socket ID: ${socket.id}, Reason: ${reason}`);
    
    // Update tracking
    connections.total--;
    if (connections.byUser[userId]) {
      connections.byUser[userId] = connections.byUser[userId].filter(id => id !== socket.id);
      if (connections.byUser[userId].length === 0) {
        delete connections.byUser[userId];
      }
    }
  });

  // Handle errors
  socket.on('error', (error) => {
    logger.error(`Socket error for user ${userId}: ${error.message}`);
  });
});

// RabbitMQ Consumer Setup with better error handling and reconnection
async function setupRabbitMQ() {
  let connection = null;
  let channel = null;
  const exchange = config.rabbitmq.exchange;
  
  const connect = async () => {
    try {
      connection = await amqp.connect({
        protocol: 'amqp',
        hostname: config.rabbitmq.host,
        port: config.rabbitmq.port,
        username: config.rabbitmq.user,
        password: config.rabbitmq.pass,
        vhost: config.rabbitmq.vhost,
        heartbeat: 30
      });
      
      connection.on('error', (err) => {
        logger.error(`RabbitMQ connection error: ${err.message}`);
        setTimeout(connect, 5000); // Reconnect after 5 seconds
      });
      
      connection.on('close', () => {
        logger.warn('RabbitMQ connection closed, attempting to reconnect...');
        setTimeout(connect, 5000); // Reconnect after 5 seconds
      });
      
      channel = await connection.createChannel();
      
      // Assert the exchange
      await channel.assertExchange(exchange, 'fanout', { durable: true });
      
      // Exclusive queue for notifications with TTL
      const queue = await channel.assertQueue('', {
        exclusive: true,
        arguments: {
          'x-message-ttl': 60000 // Messages expire after 60 seconds
        }
      });
      
      channel.bindQueue(queue.queue, exchange, '');
      
      logger.info('RabbitMQ connected and waiting for messages...');
      
      // Consume messages from the queue with error handling
      channel.consume(queue.queue, (msg) => {
        if (msg !== null) {
          try {
            const event = JSON.parse(msg.content.toString());
            
            logger.info(`Received event from RabbitMQ: ${event.type}`);
            
            // Only forward the event if we have a userId
            if (event.userId) {
              const eventType = event.type;
              const recipientExists = connections.byUser[event.userId] && connections.byUser[event.userId].length > 0;
              
              if (recipientExists) {
                logger.info(`Emitting ${eventType} to user ${event.userId}`);
                
                io.to(event.userId).emit(eventType, {
                  aggregateId: event.aggregateId,
                  userId: event.userId,
                  requestId: event.requestId,
                  type: eventType,
                  data: event.data || {},
                  timestamp: new Date().toISOString()
                });
              } else {
                logger.info(`No active connections for user ${event.userId}, event dropped`);
              }
            }
            
            // Acknowledge the message
            channel.ack(msg);
          } catch (error) {
            logger.error(`Error processing RabbitMQ message: ${error.message}`);
            // Negative acknowledge with requeue set to false to prevent redelivery of malformed messages
            channel.nack(msg, false, false);
          }
        }
      });
    } catch (error) {
      logger.error(`RabbitMQ connection error: ${error.message}`);
      setTimeout(connect, 5000); // Retry connection after 5 seconds
    }
  };
  
  // Initial connection
  connect();
}

// Statistics and monitoring endpoint
app.get('/status', (req, res) => {
  const statusInfo = {
    uptime: process.uptime(),
    timestamp: Date.now(),
    connections: {
      total: connections.total,
      byUser: Object.keys(connections.byUser).length
    },
    memory: process.memoryUsage()
  };
  
  res.status(200).json(statusInfo);
});

// Start the WebSocket server
server.listen(config.server.port, () => {
  logger.info(`WebSocket server running on port ${config.server.port}`);
  logger.info(`Server mode: ${config.server.useHTTPS ? 'HTTPS' : 'HTTP'}`);
  logger.info(`Allowed origins: ${JSON.stringify(config.cors.origin)}`);
  
  // Set up RabbitMQ connection
  setupRabbitMQ();
});
