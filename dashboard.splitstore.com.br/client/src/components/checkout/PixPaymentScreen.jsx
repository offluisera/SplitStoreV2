// dashboard.splitstore.com.br/client/src/components/checkout/PixPaymentScreen.jsx
import { useState, useEffect } from 'react';
import { ChevronRight, Copy, Check, Loader, XCircle, CheckCircle, Clock } from 'lucide-react';

const PixPaymentScreen = ({ paymentData, onBack, onSuccess }) => {
  const [copied, setCopied] = useState(false);
  const [timeRemaining, setTimeRemaining] = useState(600); // 10 minutos
  const [checkingPayment, setCheckingPayment] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState('pending'); // pending, checking, approved, expired

  useEffect(() => {
    // Timer de expiração
    const timer = setInterval(() => {
      setTimeRemaining(prev => {
        if (prev <= 1) {
          clearInterval(timer);
          setPaymentStatus('expired');
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    // Verificar pagamento a cada 5 segundos
    if (paymentStatus === 'pending') {
      const checkInterval = setInterval(() => {
        checkPaymentStatus();
      }, 5000);

      return () => clearInterval(checkInterval);
    }
  }, [paymentStatus]);

  const checkPaymentStatus = async () => {
    if (checkingPayment) return;
    
    setCheckingPayment(true);
    
    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`/api/checkout/check-payment/${paymentData.payment_id}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();
      
      if (data.status === 'approved' || data.status === 'completed') {
        setPaymentStatus('approved');
        setTimeout(() => {
          onSuccess && onSuccess(data);
        }, 2000);
      }
    } catch (error) {
      console.error('Erro ao verificar pagamento:', error);
    } finally {
      setCheckingPayment(false);
    }
  };

  const copyToClipboard = () => {
    navigator.clipboard.writeText(paymentData.pix_code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  if (paymentStatus === 'approved') {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-24 h-24 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
            <CheckCircle className="w-12 h-12 text-green-600" />
          </div>
          <h2 className="text-4xl font-black mb-4 text-green-600">Pagamento Aprovado!</h2>
          <p className="text-zinc-400 mb-8 text-lg">
            Sua loja está sendo criada agora
          </p>
          <div className="bg-green-600/10 border border-green-600/20 rounded-xl p-6">
            <p className="text-sm text-green-400">
              ✨ Você será redirecionado para o dashboard em instantes...
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (paymentStatus === 'expired') {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
          <ChevronRight className="w-4 h-4 rotate-180" />
          Voltar
        </button>

        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-20 h-20 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <XCircle className="w-10 h-10 text-red-600" />
          </div>
          <h2 className="text-3xl font-black mb-4 text-red-600">Pagamento Expirado</h2>
          <p className="text-zinc-400 mb-8">
            O tempo para pagamento expirou. Gere um novo PIX para continuar.
          </p>
          <button onClick={onBack} className="px-8 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all">
            Tentar Novamente
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        {/* Timer */}
        <div className="bg-orange-600/10 border border-orange-600/20 rounded-xl p-4 mb-8 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Clock className="w-5 h-5 text-orange-600" />
            <span className="text-sm text-zinc-400">Tempo restante</span>
          </div>
          <span className="text-2xl font-black text-orange-600">
            {formatTime(timeRemaining)}
          </span>
        </div>

        <div className="text-center mb-8">
          <h2 className="text-3xl font-black mb-2">Pague com PIX</h2>
          <p className="text-zinc-400">
            Escaneie o QR Code ou copie o código PIX
          </p>
        </div>

        {/* Informações do Pagamento */}
        <div className="bg-red-600/10 border border-red-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between mb-4">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Plano</p>
              <p className="font-black">{paymentData.plan_name}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Valor</p>
              <p className="text-2xl font-black text-red-600">
                R$ {paymentData.amount}
              </p>
            </div>
          </div>
          <div className="border-t border-white/10 pt-4">
            <p className="text-sm text-zinc-400 mb-1">Loja</p>
            <p className="font-semibold">{paymentData.store_name}</p>
            <p className="text-sm text-zinc-500">{paymentData.store_slug}.splitstore.com.br</p>
          </div>
        </div>

        {/* QR Code */}
        <div className="bg-white rounded-2xl p-8 mb-8">
          <div className="flex justify-center">
            {paymentData.qr_code_base64 ? (
              <img 
                src={`data:image/png;base64,${paymentData.qr_code_base64}`}
                alt="QR Code PIX"
                className="w-64 h-64"
              />
            ) : (
              <div className="w-64 h-64 bg-zinc-100 rounded-xl flex items-center justify-center">
                <Loader className="w-8 h-8 text-zinc-400 animate-spin" />
              </div>
            )}
          </div>
        </div>

        {/* Código PIX para Copiar */}
        <div className="bg-black/50 rounded-xl p-6 mb-8">
          <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">
            Ou copie o código PIX
          </label>
          <div className="flex gap-3">
            <div className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 overflow-hidden">
              <p className="text-sm font-mono truncate text-zinc-300">
                {paymentData.pix_code}
              </p>
            </div>
            <button
              onClick={copyToClipboard}
              className="px-6 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all flex items-center gap-2 whitespace-nowrap"
            >
              {copied ? (
                <>
                  <Check className="w-5 h-5" />
                  Copiado!
                </>
              ) : (
                <>
                  <Copy className="w-5 h-5" />
                  Copiar
                </>
              )}
            </button>
          </div>
        </div>

        {/* Instruções */}
        <div className="bg-blue-600/10 border border-blue-600/20 rounded-xl p-6 mb-8">
          <h3 className="font-bold mb-4 flex items-center gap-2">
            <span className="w-6 h-6 bg-blue-600/20 rounded-full flex items-center justify-center text-xs">1</span>
            Como pagar
          </h3>
          <ol className="space-y-3 text-sm text-zinc-400">
            <li className="flex items-start gap-3">
              <span className="flex-shrink-0 w-6 h-6 bg-blue-600/20 rounded-full flex items-center justify-center text-xs font-bold text-blue-400">1</span>
              <span>Abra o app do seu banco</span>
            </li>
            <li className="flex items-start gap-3">
              <span className="flex-shrink-0 w-6 h-6 bg-blue-600/20 rounded-full flex items-center justify-center text-xs font-bold text-blue-400">2</span>
              <span>Escolha pagar com PIX</span>
            </li>
            <li className="flex items-start gap-3">
              <span className="flex-shrink-0 w-6 h-6 bg-blue-600/20 rounded-full flex items-center justify-center text-xs font-bold text-blue-400">3</span>
              <span>Escaneie o QR Code ou cole o código copiado</span>
            </li>
            <li className="flex items-start gap-3">
              <span className="flex-shrink-0 w-6 h-6 bg-blue-600/20 rounded-full flex items-center justify-center text-xs font-bold text-blue-400">4</span>
              <span>Confirme o pagamento</span>
            </li>
          </ol>
        </div>

        {/* Status de Verificação */}
        <div className="bg-green-600/10 border border-green-600/20 rounded-xl p-6 flex items-center justify-between">
          <div className="flex items-center gap-3">
            {checkingPayment ? (
              <Loader className="w-5 h-5 text-green-600 animate-spin" />
            ) : (
              <div className="w-5 h-5 bg-green-600 rounded-full animate-pulse" />
            )}
            <span className="text-sm text-green-400">
              {checkingPayment ? 'Verificando pagamento...' : 'Aguardando pagamento'}
            </span>
          </div>
          <button
            onClick={checkPaymentStatus}
            disabled={checkingPayment}
            className="text-sm text-green-400 hover:text-green-300 transition-colors disabled:opacity-50"
          >
            Verificar agora
          </button>
        </div>
      </div>
    </div>
  );
};

export default PixPaymentScreen;