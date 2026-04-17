"""
Módulo de autenticación JWT compartido entre todos los servicios.
"""
import jwt
from datetime import datetime, timedelta
from fastapi import HTTPException, status, Depends
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from typing import Dict, Optional

# Clave secreta para firmar tokens (cambiar en producción a variable de entorno)
SECRET_KEY = "shopnow-secret-key-2024-change-in-production"
ALGORITHM = "HS256"
EXPIRATION_MINUTES = 480  # 8 hours

security = HTTPBearer()


def create_access_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    """
    Crea un token JWT con los datos proporcionados.
    
    Args:
        data: Diccionario con datos a incluir en el token (ej: {"sub": "user123"})
        expires_delta: Tiempo de expiración personalizado (opcional)
    
    Returns:
        str: Token JWT codificado
    """
    to_encode = data.copy()
    
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=EXPIRATION_MINUTES)
    
    to_encode.update({"exp": expire})
    
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt


def verify_token(credentials: HTTPAuthorizationCredentials = Depends(security)) -> Dict:
    """
    Verifica un token JWT del header Authorization.
    Usado como dependencia en los endpoints protegidos.
    
    Args:
        credentials: Credenciales extraídas del header Authorization: Bearer <token>
    
    Returns:
        dict: Payload decodificado del token
    
    Raises:
        HTTPException: Si el token es inválido o ha expirado (401)
    """
    token = credentials.credentials
    
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token ha expirado",
            headers={"WWW-Authenticate": "Bearer"},
        )
    except jwt.InvalidTokenError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token inválido",
            headers={"WWW-Authenticate": "Bearer"},
        )


def verify_token_optional(credentials: Optional[HTTPAuthorizationCredentials] = Depends(security)) -> Optional[Dict]:
    """
    Verifica un token JWT si se proporciona, pero no es requerido.
    
    Args:
        credentials: Credenciales (opcional)
    
    Returns:
        dict o None: Payload decodificado o None si no hay token
    """
    if credentials is None:
        return None
    
    return verify_token(credentials)
