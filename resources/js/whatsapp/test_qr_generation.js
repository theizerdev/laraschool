const axios = require('axios');

async function testQRGeneration() {
  console.log('🧪 Probando generación de código QR...\n');
  
  const baseURL = 'http://localhost:3001';
  const apiKey = 'test-api-key-vargas-centro';
  
  try {
    // 1. Verificar estado inicial
    console.log('📊 Verificando estado inicial...');
    const statusResponse = await axios.get(`${baseURL}/api/whatsapp/status`, {
      headers: { 'X-API-Key': apiKey }
    });
    
    console.log('Estado actual:', {
      isConnected: statusResponse.data.isConnected,
      connectionState: statusResponse.data.connectionState,
      hasQR: !!statusResponse.data.qrCode
    });
    
    // 2. Si no hay QR y no está conectado, intentar conectar
    if (!statusResponse.data.isConnected && !statusResponse.data.qrCode) {
      console.log('\n🔗 Intentando conectar...');
      const connectResponse = await axios.post(`${baseURL}/api/whatsapp/connect`, {}, {
        headers: { 'X-API-Key': apiKey }
      });
      console.log('Conexión iniciada:', connectResponse.data.message);
      
      // Esperar un momento para que se genere el QR
      console.log('⏳ Esperando 5 segundos para generación de QR...');
      await new Promise(resolve => setTimeout(resolve, 5000));
    }
    
    // 3. Intentar obtener el código QR
    console.log('\n📱 Obteniendo código QR...');
    const qrResponse = await axios.get(`${baseURL}/api/whatsapp/qr`, {
      headers: { 'X-API-Key': apiKey }
    });
    
    if (qrResponse.data.success && qrResponse.data.qr) {
      console.log('✅ Código QR generado exitosamente!');
      console.log('📊 Datos del QR:', qrResponse.data.qr.substring(0, 50) + '...');
    } else {
      console.log('❌ No se pudo obtener el código QR:', qrResponse.data.error);
      
      // 4. Si falla, intentar force reconnect
      console.log('\n🔄 Intentando force reconnect...');
      const forceReconnectResponse = await axios.post(`${baseURL}/api/whatsapp/force-reconnect`, {}, {
        headers: { 'X-API-Key': apiKey }
      });
      console.log('Force reconnect:', forceReconnectResponse.data.message);
      
      // Esperar y volver a intentar obtener QR
      console.log('⏳ Esperando 8 segundos después de force reconnect...');
      await new Promise(resolve => setTimeout(resolve, 8000));
      
      const qrResponse2 = await axios.get(`${baseURL}/api/whatsapp/qr`, {
        headers: { 'X-API-Key': apiKey }
      });
      
      if (qrResponse2.data.success && qrResponse2.data.qr) {
        console.log('✅ Código QR generado después de force reconnect!');
        console.log('📊 Datos del QR:', qrResponse2.data.qr.substring(0, 50) + '...');
      } else {
        console.log('❌ Aún no hay código QR:', qrResponse2.data.error);
      }
    }
    
    // 5. Verificar estado final
    const finalStatus = await axios.get(`${baseURL}/api/whatsapp/status`, {
      headers: { 'X-API-Key': apiKey }
    });
    
    console.log('\n📊 Estado final:');
    console.log('- Conectado:', finalStatus.data.isConnected);
    console.log('- Estado:', finalStatus.data.connectionState);
    console.log('- Intentos de reconexión:', finalStatus.data.reconnectAttempts);
    console.log('- Tiempo activo:', new Date(finalStatus.data.lastSeen).toLocaleString());
    
  } catch (error) {
    console.error('❌ Error en prueba:', error.response?.data || error.message);
  }
}

// Ejecutar la prueba
testQRGeneration().then(() => {
  console.log('\n✅ Prueba completada');
}).catch(console.error);