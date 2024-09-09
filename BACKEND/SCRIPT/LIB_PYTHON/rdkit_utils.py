import sys
from rdkit import Chem

def sdfToSMILES(sdf):
    mol = Chem.MolFromMolBlock(sdf)
    return Chem.MolToSmiles(mol)

def SMILEStoInchi(smi):
    mol = Chem.MolFromSmiles(smi)
    result = {'INCHI':'','INCHI_KEY':''}
    try:
        result['INCHI'] = Chem.MolToInchi(mol)
        if (result['INCHI']!=''):result['INCHI_KEY']=Chem.InchiToInchiKey(result['INCHI'])
    except: print("ISSUE")
    
    return result


def file_SDFToSMI(file_path,file_out_path,prop='_Name'):
    
    print(prop)
    o = open(file_out_path,'w')
    suppl = Chem.SDMolSupplier(file_path)
    for mol in suppl:
        print(list(mol.GetPropNames()))
        SMI=Chem.MolToSmiles(mol)
        o.write(SMI+" "+mol.GetProp(prop)+"\n")
    o.close()

    



def file_SMILEStoInchi(file_path,file_out_path):
    
    same_inchi=0
    same_inchi_key=0
    tot=0
    o = open(file_out_path,'w')
    with open(file_path,'r') as f:
        for line in f:
            
            line = line.strip()
            if line == '':
                continue
            tot+=1
            tmp = line.split(' ')
            if len(tmp) != 2:
                continue
            tmp_head=tmp[1].split('|')
            
            str=tmp_head[4]
            
            result=SMILEStoInchi(str)
            if (tmp_head[1]==result['INCHI']):same_inchi+=1
            if (tmp_head[2]==result['INCHI_KEY']):same_inchi_key+=1
            tmp_head[1]=result['INCHI']
            tmp_head[2]=result['INCHI_KEY']
            str+=" "+tmp_head[0]+"|"+tmp_head[1]+"|"+tmp_head[2]+"|"+tmp_head[3]+"|"+tmp_head[4]+"|"+tmp_head[5]+"\n"
            o.write(str)
    o.close()

    print("SAME_INCHI:")
    print(same_inchi)
    print("INCHI_KEY:")
    print(same_inchi_key)
    print("TOT:")
    print(tot)
    

if (sys.argv[1]=='sdf_to_smi'):
    file_SDFToSMI(sys.argv[2],sys.argv[3],sys.argv[4])

if (sys.argv[1]=='smiles_to_inchi_file'):
    file_SMILEStoInchi(sys.argv[2],sys.argv[3])