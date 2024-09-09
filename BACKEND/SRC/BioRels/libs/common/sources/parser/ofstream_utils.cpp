#include <sstream>
#include "headers/parser/ofstream_utils.h"
#include "headers/graph/graph.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/mmbond_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/sequence/seqbase.h"
#undef NDEBUG /// Active assertion in release
std::ostream & operator << (std::ostream & out, const protspace::Edge &qq)
{
    out<< "Edge " << qq.getDot1().getMID()<< " <-> "<< qq.getDot2().getMID()<<"\n";
    return out;
}
std::ostream & operator << (std::ostream & out, const protspace::Vertex &ve)
{
    out << "VERTEX " << ve.getMID()<< "(FID:"<< ve.getFID()<<")\n";

    for(size_t iEd=0;iEd < ve.numDot();++iEd)
        out<< "  |-> " << ve.getEdge(iEd)<< "\n";

    return out;
}


std::ostream & operator << (std::ostream & out, const protspace::Graph &gr)
{
    for (size_t i=0;i<gr.numVertex();++i)
        out<< gr.getVertex(i);
    for (size_t i=0;i<gr.numEdges();++i)
        out << gr.getEdge(i);
    return out;
}


std::ostream & operator << (std::ostream & out, const protspace::MMBond &bd)
{
     out<<"|>Bond("<< bd.getMID()
        <<"/"      <<bd.getFID()
        <<") :"    <<bd.getDot1().getIdentifier()
        <<"<->"    <<bd.getDot2().getIdentifier()
        <<"\t"     <<protspace::getBondType(bd);
     return out;
}

std::ostream& operator <<(std::ostream& out, const protspace::MMAtom& atom)
{

    out<< "--- ATOM " << atom.getName()
        << "("<<atom.getFID()
        << "/"<<atom.getMID()<<") "
        << atom.getMOL2()<< " --"
        << atom.getParent().getName()<<"--";
    out << "(" << (static_cast<unsigned>(atom.getAtomicNum()))
        << " -" << atom.getElement()
        <<"-" << ")--"<<atom.pos()<<"--";
const protspace::MMResidue& pRes=atom.getResidue();
    out << "FCharge: "<< (signed)atom.getFormalCharge()
        << "--"  << pRes.getChainName()
        << "::"             <<pRes.getName()
        << "("             << pRes.getFID()
        << "/"              << pRes.getMID()<<")\n";

    out << "--"<<atom.numDot()<<"/"<<atom.numBonds()<<"\n";

    for (size_t i=0;i<atom.numBonds();++i)
    {
        protspace::MMBond& bond =atom.getBond(i);
        out<< " |-"<<bond<<"\n";
    }
    return out;
}
std::ostream& operator <<(std::ostream& out, const protspace::MMRing& pRing)
{
    out<<"RING ";
    if (pRing.isAromatic()) out<< "AR {";else out<<"AL {";
    out<<pRing.getResidue().getIdentifier() << "\t";
    for (size_t i=0;i<pRing.numAtoms();++i)
    {
        const protspace::MMAtom& atm=pRing.getAtom(i);
       out<<atm.getName()
          <<atm.getMID()<<" ; ";
    }
    out << " } ";

    return out;
}
std::ostream& operator <<(std::ostream& out, const protspace::MMResidue& pRes)
{
out << "### RESIDUE : "<< pRes.getIdentifier()<<"--"
    << "CLASS : "<< protspace::getResidueType(pRes)<<"--ATOM:"<<pRes.numAtoms()<<"\n";
for (size_t iAtm=0;iAtm < pRes.numAtoms();++iAtm)
{
    const protspace::MMAtom& pAtom=pRes.getAtom(iAtm);
    out<<"ATOM|"<<pAtom.getIdentifier()
      <<"\t"<<pAtom.getElement()
     <<"::"<<pAtom.getMOL2()<<"\n";
}
return out;
}
std::ostream& operator <<(std::ostream& out, const protspace::MacroMole& mole)
{

        for (size_t iAtm=0 ;iAtm <mole.numAtoms()  ; ++iAtm)
                out<< mole.getAtom(iAtm);

        for (size_t iBd =0 ;iBd  <mole.numBonds() ; ++iBd )
            out<< mole.getBond(iBd)<<"\n";
        for (size_t iBd =0 ;iBd  < mole.numResidue(); ++iBd )
            out<< mole.getResidue(iBd);

        for(size_t iRing=0;iRing < mole.numRings();++iRing)
        {
            out<<mole.getRing(iRing);
        }
    return out;
}

std::ostream& operator<<(std::ostream& out, const protspace::PhysProp& seq)
{
    for(auto it=CHEMPROP::typeToName.begin();it!=CHEMPROP::typeToName.end();++it)
    {
        if (seq.hasProperty((*it).first))out<<(*it).second<<"|";
    }
    return out;
}

void readSerializedString(std::ifstream& ifs,std::string& value)
{
    size_t length=0;
    ifs.read((char*)&length,sizeof(size_t));
    char* temp = new char[length+1];
    ifs.read(temp,length);
    temp[length]='\0';
    value=temp;
    delete[]temp;
}

void saveSerializedString(std::ofstream& ofs, const std::string& value)
{
    const size_t len =value.length();
    ofs.write((char*)&len,sizeof(size_t));
    ofs.write(value.c_str(),len);
}


std::ostream& operator<<(std::ostream& out, const protspace::SeqBase& seq)
{

    const size_t AALen(protspace::SeqBase::mAASeq.length());
    out<<seq.getName()<<"\n";
    for(const unsigned char& val:seq.getSeq())
    {
        if (val == AALen) out<< seq.getGapChar();
        else out<< seq.getLetter(val);
    }
    return out;
}


void safeGetline(std::istream& is,std::string& line)
{
    line.clear();

    // The characters in the stream are read one-by-one using a std::streambuf.
    // That is faster than reading them one-by-one using the std::istream.
    // Code that uses streambuf this way must be guarded by a sentry object.
    // The sentry object performs various tasks,
    // such as thread synchronization and updating the stream state.

    std::istream::sentry se(is, true);
    std::streambuf* sb = is.rdbuf();

    for(;;) {
        int c = sb->sbumpc();
        switch (c) {
        case '\n':return ;
        case '\r':
            if(sb->sgetc() == '\n')
                sb->sbumpc();return ;
        case EOF:
            // Also handle the case when the last line has no line ending
            if(line.empty())
                is.setstate(std::ios::eofbit);
            return ;
        default:
            line+= (char)c;
        }
    }
}
