// src/components/checkout/PaymentGateway.jsx
import React, { useState, useEffect } from 'react';
import { ChevronRight, Loader, CheckCircle, XCircle } from 'lucide-react';
import { checkoutAPI } from '../../services/api';

const PaymentGateway = ({ selectedPlan, storeData, onBack }) => {
  const [loading, setLoading] = useState(true);
  const [paymentUrl, setPaymentUrl] = useState('');
  const [paymentId, setPaymentId] = useState('');
  const [error, setError] = useState('');
  const [paymentStatus, setPaymentStatus] = useState('pending'); // pending, processing, completed, failed

  useEffect(() => {
    createCheckout();
  }, []);

  const createCheckout = async () => {
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('auth_token');
      
      const response = await checkoutAPI.createCheckout({
        token,
        plan_id: selectedPlan.id,
        store_name: storeData.storeName,
        store_slug: storeData.storeSlug,
        coupon_code: storeData.coupon?.code || ''
      });

      if (response.data.success) {
        setPaymentUrl(response.data.payment_url);
        setPaymentId(response.data.payment_id);
        
        // Redirecionar para URL de pagamento
        if (response.data.payment_url) {
          window.location.href = response.data.payment_url;
        }
      } else {
        setError(response.data.error || 'Erro ao criar checkout');
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Erro ao processar pagamento');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-20 h-20 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <Loader className="w-10 h-10 text-red-600 animate-spin" />
          </div>
          <h2 className="text-3xl font-black mb-4">Processando pagamento...</h2>
          <p className="text-zinc-400 mb-8">
            Aguarde enquanto preparamos seu checkout
          </p>
          <div className="bg-black/50 rounded-xl p-6 text-left">
            <div className="space-y-2 text-sm text-zinc-400">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-red-600 rounded-full animate-pulse"></div>
                Criando sua loja...
              </div>
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-red-600 rounded-full animate-pulse"></div>
                Gerando link de pagamento...
              </div>
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-red-600 rounded-full animate-pulse"></div>
                Configurando integração...
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Voltar
        </button>

        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-20 h-20 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <XCircle className="w-10 h-10 text-red-600" />
          </div>
          <h2 className="text-3xl font-black mb-4 text-red-600">Erro no Pagamento</h2>
          <p className="text-zinc-400 mb-8">
            {error}
          </p>
          <div className="flex gap-4 justify-center">
            <button
              onClick={onBack}
              className="px-8 py-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl font-bold transition-all"
            >
              Voltar
            </button>
            <button
              onClick={createCheckout}
              className="px-8 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all flex items-center gap-2"
            >
              Tentar Novamente
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
        <div className="w-20 h-20 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
          <CheckCircle className="w-10 h-10 text-green-600" />
        </div>
        <h2 className="text-3xl font-black mb-4">Redirecionando para pagamento...</h2>
        <p className="text-zinc-400 mb-8">
          Você será redirecionado para a página de pagamento em instantes
        </p>
        
        {paymentUrl && (
          <div className="bg-black/50 rounded-xl p-6 mb-8">
            <p className="text-xs text-zinc-500 mb-4">
              Caso não seja redirecionado automaticamente, clique no botão abaixo:
            </p>
            <a
              href={paymentUrl}
              className="inline-flex items-center gap-2 px-8 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all"
            >
              Ir para Pagamento
              <ChevronRight className="w-5 h-5" />
            </a>
          </div>
        )}

        <div className="bg-blue-600/10 border border-blue-600/20 rounded-xl p-6">
          <p className="text-sm text-blue-400">
            💡 Após o pagamento, sua loja será ativada automaticamente!
          </p>
        </div>
      </div>
    </div>
  );
};

export default PaymentGateway;