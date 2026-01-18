// auth.splitstore.com.br/src/pages/Register.jsx
import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';

export default function Register() {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState({
    nome: '',
    sobrenome: '',
    telefone: '',
    email: '',
    cpf: '',
    code: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);
  const [resendTimer, setResendTimer] = useState(0);

  // Particles effect
  useEffect(() => {
    if (window.particlesJS) {
      window.particlesJS("particles-js", {
        particles: {
          number: { value: 60, density: { enable: true, value_area: 800 } },
          color: { value: "#ef4444" },
          shape: { type: "circle" },
          opacity: {
            value: 0.1,
            random: true,
            anim: { enable: true, speed: 1, opacity_min: 0.05, sync: false }
          },
          size: {
            value: 3,
            random: true,
            anim: { enable: true, speed: 2, size_min: 0.5, sync: false }
          },
          line_linked: {
            enable: true,
            distance: 150,
            color: "#ef4444",
            opacity: 0.05,
            width: 1
          },
          move: {
            enable: true,
            speed: 1,
            direction: "none",
            random: true,
            out_mode: "out"
          }
        },
        interactivity: {
          detect_on: "canvas",
          events: {
            onhover: { enable: true, mode: "grab" },
            resize: true
          },
          modes: {
            grab: { distance: 140, line_linked: { opacity: 0.2 } }
          }
        },
        retina_detect: true
      });
    }
  }, []);

  // Timer para reenvio de código
  useEffect(() => {
    if (resendTimer > 0) {
      const timer = setTimeout(() => setResendTimer(resendTimer - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [resendTimer]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    
    // Máscaras
    let maskedValue = value;
    if (name === 'telefone') {
      maskedValue = value.replace(/\D/g, '').replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (name === 'cpf') {
      maskedValue = value.replace(/\D/g, '').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (name === 'code') {
      maskedValue = value.replace(/\D/g, '').slice(0, 6);
    }
    
    setFormData(prev => ({
      ...prev,
      [name]: maskedValue
    }));
    
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateStep1 = () => {
    const newErrors = {};
    
    if (!formData.nome.trim()) {
      newErrors.nome = 'Nome é obrigatório';
    }
    if (!formData.sobrenome.trim()) {
      newErrors.sobrenome = 'Sobrenome é obrigatório';
    }
    if (!formData.telefone) {
      newErrors.telefone = 'Telefone é obrigatório';
    } else if (formData.telefone.replace(/\D/g, '').length !== 11) {
      newErrors.telefone = 'Telefone inválido';
    }
    if (!formData.email) {
      newErrors.email = 'E-mail é obrigatório';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'E-mail inválido';
    }
    if (!formData.cpf) {
      newErrors.cpf = 'CPF é obrigatório';
    } else if (formData.cpf.replace(/\D/g, '').length !== 11) {
      newErrors.cpf = 'CPF inválido';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleStep1Submit = async (e) => {
    e.preventDefault();
    
    if (!validateStep1()) return;
    
    setLoading(true);
    setMessage(null);
    
    try {
      const response = await fetch('/api/register/send-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nome: formData.nome,
          sobrenome: formData.sobrenome,
          telefone: formData.telefone.replace(/\D/g, ''),
          email: formData.email,
          cpf: formData.cpf.replace(/\D/g, '')
        })
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setMessage({ type: 'success', text: 'Código enviado para seu e-mail!' });
        setStep(2);
        setResendTimer(60);
      } else {
        if (data.errors) {
          setErrors(data.errors);
        } else {
          setMessage({ type: 'error', text: data.error || 'Erro ao enviar código' });
        }
      }
    } catch (error) {
      setMessage({ type: 'error', text: 'Erro ao enviar código. Tente novamente.' });
    } finally {
      setLoading(false);
    }
  };

  const handleResendCode = async () => {
    if (resendTimer > 0) return;
    
    setLoading(true);
    setMessage(null);
    
    try {
      const response = await fetch('/api/register/resend-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: formData.email })
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setMessage({ type: 'success', text: 'Código reenviado!' });
        setResendTimer(60);
      } else {
        setMessage({ type: 'error', text: data.error || 'Erro ao reenviar código' });
      }
    } catch (error) {
      setMessage({ type: 'error', text: 'Erro ao reenviar código.' });
    } finally {
      setLoading(false);
    }
  };

  const handleStep2Submit = async (e) => {
    e.preventDefault();
    
    if (!formData.code || formData.code.length !== 6) {
      setErrors({ code: 'Digite o código de 6 dígitos' });
      return;
    }
    
    setLoading(true);
    setMessage(null);
    
    try {
      const response = await fetch('/api/register/verify-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: formData.email,
          code: formData.code
        })
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setMessage({ type: 'success', text: 'E-mail verificado com sucesso!' });
        
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        
        const redirectUrl = `https://dashboard.splitstore.com.br#token=${data.token}`;
        
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 1500);
      } else {
        setMessage({ type: 'error', text: data.error || 'Código inválido' });
      }
    } catch (error) {
      setMessage({ type: 'error', text: 'Erro ao verificar código.' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-black text-white flex items-center justify-center p-4 relative overflow-hidden">
      <div id="particles-js" className="absolute inset-0 z-0"></div>
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(220,38,38,0.1)_0%,_transparent_70%)] z-0"></div>

      <div className="relative z-10 w-full max-w-md">
        <div className="text-center mb-8">
          <a href="https://splitstore.com.br" className="inline-flex items-center gap-3 mb-2">
            <div className="w-12 h-12 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black shadow-lg shadow-red-900/40">
              S
            </div>
            <span className="text-2xl font-black tracking-tighter uppercase">
              Split<span className="text-red-600">Store</span>
            </span>
          </a>
          <p className="text-zinc-500 text-sm mt-2">
            {step === 1 ? 'Crie sua conta gratuitamente' : 'Verifique seu e-mail'}
          </p>
        </div>

        <div className="bg-gradient-to-br from-white/[0.03] to-white/[0.01] backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
          
          {/* Progress Steps */}
          <div className="flex items-center justify-center gap-3 mb-8">
            <div className={`flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm ${
              step >= 1 ? 'bg-red-600 text-white' : 'bg-white/5 text-zinc-600'
            }`}>
              1
            </div>
            <div className={`h-0.5 w-12 ${step >= 2 ? 'bg-red-600' : 'bg-white/10'}`}></div>
            <div className={`flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm ${
              step >= 2 ? 'bg-red-600 text-white' : 'bg-white/5 text-zinc-600'
            }`}>
              2
            </div>
          </div>

          {message && (
            <div className={`flex items-center gap-3 p-4 rounded-xl mb-6 ${
              message.type === 'success' 
                ? 'bg-green-600/10 border border-green-600/20 text-green-500' 
                : 'bg-red-600/10 border border-red-600/20 text-red-500'
            }`}>
              <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {message.type === 'success' ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                )}
              </svg>
              <p className="text-sm font-semibold">{message.text}</p>
            </div>
          )}

          {/* STEP 1: Dados Pessoais */}
          {step === 1 && (
            <form onSubmit={handleStep1Submit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                    Nome
                  </label>
                  <input
                    type="text"
                    name="nome"
                    value={formData.nome}
                    onChange={handleChange}
                    className={`w-full bg-white/5 border ${
                      errors.nome ? 'border-red-600/50' : 'border-white/10'
                    } rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600/50`}
                    placeholder="João"
                  />
                  {errors.nome && (
                    <p className="text-red-500 text-xs mt-1">{errors.nome}</p>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                    Sobrenome
                  </label>
                  <input
                    type="text"
                    name="sobrenome"
                    value={formData.sobrenome}
                    onChange={handleChange}
                    className={`w-full bg-white/5 border ${
                      errors.sobrenome ? 'border-red-600/50' : 'border-white/10'
                    } rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600/50`}
                    placeholder="Silva"
                  />
                  {errors.sobrenome && (
                    <p className="text-red-500 text-xs mt-1">{errors.sobrenome}</p>
                  )}
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                  Telefone
                </label>
                <input
                  type="text"
                  name="telefone"
                  value={formData.telefone}
                  onChange={handleChange}
                  className={`w-full bg-white/5 border ${
                    errors.telefone ? 'border-red-600/50' : 'border-white/10'
                  } rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600/50`}
                  placeholder="(00) 00000-0000"
                  maxLength="15"
                />
                {errors.telefone && (
                  <p className="text-red-500 text-xs mt-1">{errors.telefone}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                  E-mail
                </label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  className={`w-full bg-white/5 border ${
                    errors.email ? 'border-red-600/50' : 'border-white/10'
                  } rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600/50`}
                  placeholder="seu@email.com"
                />
                {errors.email && (
                  <p className="text-red-500 text-xs mt-1">{errors.email}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                  CPF
                </label>
                <input
                  type="text"
                  name="cpf"
                  value={formData.cpf}
                  onChange={handleChange}
                  className={`w-full bg-white/5 border ${
                    errors.cpf ? 'border-red-600/50' : 'border-white/10'
                  } rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600/50`}
                  placeholder="000.000.000-00"
                  maxLength="14"
                />
                {errors.cpf && (
                  <p className="text-red-500 text-xs mt-1">{errors.cpf}</p>
                )}
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 text-white py-4 rounded-xl font-black uppercase tracking-wider transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 mt-6"
              >
                {loading ? (
                  <>
                    <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Enviando...
                  </>
                ) : (
                  <>
                    Continuar
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                  </>
                )}
              </button>
            </form>
          )}

          {/* STEP 2: Verificação de Código */}
          {step === 2 && (
            <form onSubmit={handleStep2Submit} className="space-y-6">
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-red-600/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                  <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <h3 className="text-xl font-black mb-2">Verifique seu E-mail</h3>
                <p className="text-zinc-400 text-sm">
                  Enviamos um código de 6 dígitos para<br/>
                  <span className="text-white font-semibold">{formData.email}</span>
                </p>
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2 text-center">
                  Código de Verificação
                </label>
                <input
                  type="text"
                  name="code"
                  value={formData.code}
                  onChange={handleChange}
                  className={`w-full bg-white/5 border ${
                    errors.code ? 'border-red-600/50' : 'border-white/10'
                  } rounded-xl px-4 py-4 text-2xl text-center font-bold tracking-[0.5em] focus:outline-none focus:border-red-600/50`}
                  placeholder="000000"
                  maxLength="6"
                />
                {errors.code && (
                  <p className="text-red-500 text-xs mt-2 text-center">{errors.code}</p>
                )}
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 text-white py-4 rounded-xl font-black uppercase tracking-wider transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-red-600/30 flex items-center justify-center gap-2"
              >
                {loading ? (
                  <>
                    <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Verificando...
                  </>
                ) : (
                  'Verificar E-mail'
                )}
              </button>

              <div className="text-center">
                {resendTimer > 0 ? (
                  <p className="text-zinc-500 text-sm">
                    Reenviar código em <span className="text-red-600 font-bold">{resendTimer}s</span>
                  </p>
                ) : (
                  <button
                    type="button"
                    onClick={handleResendCode}
                    disabled={loading}
                    className="text-red-600 hover:text-red-500 text-sm font-semibold transition-colors"
                  >
                    Reenviar código
                  </button>
                )}
              </div>

              <button
                type="button"
                onClick={() => setStep(1)}
                className="w-full text-zinc-400 hover:text-white text-sm font-semibold transition-colors"
              >
                ← Voltar
              </button>
            </form>
          )}

          {step === 1 && (
            <>
              <div className="relative my-8">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-white/10"></div>
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                  <span className="bg-gradient-to-br from-white/[0.03] to-white/[0.01] px-4 text-zinc-600 font-bold tracking-wider">
                    ou
                  </span>
                </div>
              </div>

              <div className="text-center">
                <p className="text-zinc-500 text-sm mb-4">
                  Já tem uma conta?
                </p>
                <Link
                  to="/login"
                  className="inline-block w-full bg-white/5 hover:bg-white/10 border border-white/10 hover:border-red-600/30 text-white py-3.5 rounded-xl font-bold uppercase tracking-wider transition-all text-sm"
                >
                  Fazer Login
                </Link>
              </div>
            </>
          )}
        </div>

        <div className="text-center mt-8">
          <p className="text-zinc-700 text-xs">
            © 2026 SplitStore • Todos os direitos reservados
          </p>
        </div>
      </div>
    </div>
  );
}